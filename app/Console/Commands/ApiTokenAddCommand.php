<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Models\TokenType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ApiTokenAddCommand extends Command
{
    protected $signature = 'api-token:add
                            {--account= : ID аккаунта}
                            {--service= : ID или slug API-сервиса}
                            {--token-type= : ID или slug типа токена}
                            {--inactive : Создать токен неактивным}
                            {--expires-at= : Дата окончания действия токена}';

    protected $description = 'Добавить API-токен для аккаунта';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (Account::query()->doesntExist()) {
            $this->error('Сначала добавьте хотя бы один аккаунт.');
            return self::FAILURE;
        }

        if (ApiService::query()->doesntExist()) {
            $this->error('Сначала добавьте хотя бы один API-сервис.');
            return self::FAILURE;
        }

        $account = $this->resolveAccount();
        if (!$account) {
            return self::FAILURE;
        }

        $apiService = $this->resolveApiService();
        if (!$apiService) {
            return self::FAILURE;
        }

        $allowedTokenTypes = $apiService
            ->tokenTypes()
            ->orderBy('name')
            ->get();

        if ($allowedTokenTypes->isEmpty()) {
            $this->error(
                "Для API-сервиса «{$apiService->name}» "
                . 'не настроены разрешённые типы токенов.'
            );
            return self::FAILURE;
        }

        $tokenType = $this->resolveTokenType($allowedTokenTypes);
        if (!$tokenType) {
            return self::FAILURE;
        }

        $tokenExists = ApiToken::query()
            ->where('account_id', $account->id)
            ->where('api_service_id', $apiService->id)
            ->where('token_type_id', $tokenType->id)
            ->exists();

        if ($tokenExists) {
            $this->error(
                'Для этого аккаунта уже существует токен '
                . "типа «{$tokenType->name}» "
                . "для сервиса «{$apiService->name}»."
            );
            return self::FAILURE;
        }

        $credentials = $this->askCredentials($tokenType);
        if ($credentials === null) {
            return self::FAILURE;
        }

        $expiresAt = $this->resolveExpiresAt();
        if ($expiresAt === false) {
            return self::FAILURE;
        }

        $apiToken = ApiToken::query()->create([
            'account_id' => $account->id,
            'api_service_id' => $apiService->id,
            'token_type_id' => $tokenType->id,
            'credentials' => $credentials,
            'is_active' => !$this->option('inactive'),
            'expires_at' => $expiresAt,
            'last_used_at' => null,
        ]);
        $this->newLine();
        $this->info('API-токен успешно добавлен.');
        $this->line("ID: {$apiToken->id}");
        $this->line("Компания: {$account->company->name}");
        $this->line("Аккаунт: {$account->name}");
        $this->line("API-сервис: {$apiService->name}");
        $this->line("Тип токена: {$tokenType->name}");
        $this->line('Статус: ' . ($apiToken->is_active ? 'активен' : 'неактивен'));
        $this->line('Истекает: ' . ($apiToken->expires_at?->format('Y-m-d H:i:s') ?? 'не указано'));

        return self::SUCCESS;
    }

    private function resolveAccount(): ?Account
    {
        $accountOption = $this->option('account');
        if ($accountOption !== null) {
            $account = Account::query()
                ->with('company')
                ->find($accountOption);
            if (!$account instanceof Account) {
                $this->error(
                    "Аккаунт с ID {$accountOption} не найден."
                );
                return null;
            }
            return $account;
        }

        $accounts = Account::query()
            ->with('company')
            ->orderBy('name')
            ->get();

        $choices = $accounts
            ->map(
                fn (Account $account) =>
                    "{$account->name} / {$account->company->name} "
                    . "(ID: {$account->id})"
            )
            ->values()
            ->all();

        $selectedLabel = $this->choice(
            'Выберите аккаунт',
            $choices
        );

        $selectedIndex = array_search(
            $selectedLabel,
            $choices,
            true
        );

        return $accounts->get($selectedIndex);
    }

    private function resolveApiService(): ?ApiService
    {
        $serviceOption = $this->option('service');

        if ($serviceOption !== null) {
            $apiService = ApiService::query()
                ->where('id', $serviceOption)
                ->orWhere('slug', $serviceOption)
                ->first();

            if (!$apiService instanceof ApiService) {
                $this->error(
                    "API-сервис «{$serviceOption}» не найден."
                );
                return null;
            }
            return $apiService;
        }

        $services = ApiService::query()
            ->orderBy('name')
            ->get();

        $choices = $services
            ->map(
                fn (ApiService $service) =>
                "{$service->name} ({$service->slug})"
            )
            ->values()
            ->all();

        $selectedLabel = $this->choice(
            'Выберите API-сервис',
            $choices
        );

        $selectedIndex = array_search(
            $selectedLabel,
            $choices,
            true
        );

        return $services->get($selectedIndex);
    }

    private function resolveTokenType(Collection $allowedTokenTypes): ?TokenType
    {
        $tokenTypeOption = $this->option('token-type');

        if ($tokenTypeOption !== null) {
            $tokenType = $allowedTokenTypes->first(
                fn (TokenType $type) =>
                    (string) $type->id === (string) $tokenTypeOption
                    || $type->slug === $tokenTypeOption
            );

            if (!$tokenType instanceof TokenType) {
                $this->error(
                    'Указанный тип токена не разрешён '
                    . 'для выбранного API-сервиса.'
                );
                return null;
            }
            return $tokenType;
        }

        $choices = $allowedTokenTypes
            ->map(
                fn (TokenType $tokenType) =>
                "{$tokenType->name} ({$tokenType->slug})"
            )
            ->values()
            ->all();

        $selectedLabel = $this->choice(
            'Выберите тип токена',
            $choices
        );

        $selectedIndex = array_search(
            $selectedLabel,
            $choices,
            true
        );

        return $allowedTokenTypes->get($selectedIndex);
    }

    private function askCredentials(TokenType $tokenType): ?array
    {
        return match ($tokenType->slug) {
            'bearer-token' => $this->askBearerCredentials(),
            'api-key' => $this->askApiKeyCredentials(),
            'query-key' => $this->askQueryKeyCredentials(),
            'login-password' => $this->askLoginPasswordCredentials(),
            default => $this->askCustomCredentials(),
        };
    }

    private function askBearerCredentials(): ?array
    {
        $token = $this->secret('Введите bearer token');
        if (!$token) {
            $this->error('Токен не может быть пустым.');
            return null;
        }
        return [
            'token' => $token,
        ];
    }

    private function askApiKeyCredentials(): ?array
    {
        $keyName = $this->ask('Название заголовка', 'X-API-Key');
        $value = $this->secret('Введите API key');
        if (!$value) {
            $this->error('API key не может быть пустым.');
            return null;
        }
        return [
            'key_name' => $keyName,
            'value' => $value,
        ];
    }

    private function askQueryKeyCredentials(): ?array
    {
        $parameter = $this->ask(
            'Название query-параметра',
            'key'
        );
        $value = $this->secret('Введите значение ключа');
        if (!$value) {
            $this->error('Значение ключа не может быть пустым.');
            return null;
        }
        return [
            'parameter' => $parameter,
            'value' => $value,
        ];
    }

    private function askLoginPasswordCredentials(): ?array
    {
        $login = $this->ask('Введите логин');
        $password = $this->secret('Введите пароль');

        if (!$login || !$password) {
            $this->error('Логин и пароль обязательны.');
            return null;
        }
        return [
            'login' => $login,
            'password' => $password,
        ];
    }

    private function askCustomCredentials(): ?array
    {
        $json = $this->ask(
            'Введите credentials в формате JSON'
        );
        try {
            $credentials = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            $this->error('Некорректный JSON.');
            return null;
        }

        if (!is_array($credentials) || $credentials === []) {
            $this->error(
                'Credentials должны быть непустым JSON-объектом.'
            );
            return null;
        }
        return $credentials;
    }

    private function resolveExpiresAt(): Carbon|false|null
    {
        $value = $this->option('expires-at');
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            $this->error('Некорректная дата expires-at.');
            return false;
        }
    }
}
