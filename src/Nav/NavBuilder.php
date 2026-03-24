<?php

namespace App\Nav;

class NavBuilder
{
    /** @var NavItem[] */
    private array  $items;
    private bool   $isLoggedIn;
    private string $currentUrl;

    public function __construct(array $items, bool $isLoggedIn, string $currentUrl)
    {
        $this->items      = $this->buildItems($items);
        $this->isLoggedIn = $isLoggedIn;
        $this->currentUrl = rtrim($currentUrl, '/');
    }

    /**
     * Конвертира сурови масиви в NavItem обекти (рекурсивно).
     */
    private function buildItems(array $items): array
    {
        return array_map(function (array $item) {
            $children = isset($item['children'])
                ? $this->buildItems($item['children'])
                : [];

            return new NavItem(
                label:    $item['label'],
                url:      $item['url'],
                auth:     $item['auth']  ?? false,
                icon:     $item['icon']  ?? null,
                badge:    $item['badge'] ?? null,
                children: $children,
            );
        }, $items);
    }

    /**
     * Връща само видимите елементи според auth статуса.
     */
    public function build(): array
    {
        return $this->filterItems($this->items);
    }

    private function filterItems(array $items): array
    {
        $filtered = [];

        foreach ($items as $item) {
            // Пропускаме auth елементи ако не е логнат
            if ($item->auth && !$this->isLoggedIn) {
                continue;
            }

            // Филтрираме и децата рекурсивно
            if ($item->hasChildren()) {
                $children = $this->filterItems($item->children);
                $item = new NavItem(
                    label:    $item->label,
                    url:      $item->url,
                    auth:     $item->auth,
                    icon:     $item->icon,
                    badge:    $item->badge,
                    children: $children,
                );
            }

            $filtered[] = $item;
        }

        return $filtered;
    }

    public function getCurrentUrl(): string
    {
        return $this->currentUrl;
    }
}