<?php

namespace App\Nav;

class NavItem
{
    public function __construct(
        public readonly string  $label,
        public readonly string  $url,
        public readonly bool    $auth     = false,
        public readonly ?string $icon     = null,
        public readonly ?string $badge    = null,
        public readonly array   $children = [],
    ) {}

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function isActive(string $currentUrl): bool
    {
        if (rtrim($currentUrl, '/') === rtrim($this->url, '/')) {
            return true;
        }
        // активен ако някое дете е активно
        foreach ($this->children as $child) {
            if ($child->isActive($currentUrl)) {
                return true;
            }
        }
        return false;
    }
}