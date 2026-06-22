<?php

namespace App\Console\Commands;

use App\Models\TokenType;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TokenTypeAddCommand extends Command
{
    protected $signature = 'token-type:add
                            {name? : Название типа токена}
                            {--slug= : Уникальный идентификатор}';
    protected $description = 'Добавить новый тип API-токена';
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name') ?: $this->ask('Введите название типа токена');
        $name = trim((string) $name);
        if ($name === '') {
            $this->error('Название типа токена не может быть пустым.');
            return self::FAILURE;
        }

        $slug = $this->option('slug');
        if (!$slug) {
            $defaultSlug = Str::slug($name);
            $slug = $this->ask(
                'Введите slug типа токена',
                $defaultSlug
            );
        }
        $slug = trim((string) $slug);
        if ($slug === '') {
            $this->error('Slug типа токена не может быть пустым.');
            return self::FAILURE;
        }
        if (TokenType::query()->where('slug', $slug)->exists()) {
            $this->error(
                "Тип токена со slug «{$slug}» уже существует."
            );
            return self::FAILURE;
        }

        $tokenType = TokenType::query()->create([
            'name' => $name,
            'slug' => $slug,
        ]);
        $this->newLine();
        $this->info('Тип токена успешно добавлен.');
        $this->line("ID: {$tokenType->id}");
        $this->line("Название: {$tokenType->name}");
        $this->line("Slug: {$tokenType->slug}");

        return self::SUCCESS;
    }
}
