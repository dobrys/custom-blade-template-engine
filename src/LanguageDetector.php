<?php

namespace App;

class LanguageDetector
{
    protected array $languages;
    protected string $host;
    protected string $language;
    protected string $locale;
    protected string $protocol;
    protected string $direction = 'ltr';

    public function __construct(array $languages, ?string $host = null)
    {
        $this->languages = $languages;
        $this->host = strtolower($host ?? ($_SERVER['HTTP_HOST'] ?? ''));
        $this->protocol = $this->detectProtocol();
        $this->detect();
        $this->applySession();
    }
    protected function applySession(): void
    {
        // Ако сесията има запазен locale — той има приоритет пред subdomain
        $saved = $_SESSION['app_locale'] ?? null;
        if ($saved && $this->isValidLocale($saved)) {
            $this->setLocale($saved);
        }
    }

    public function persist(): void
    {
        // Единична точка за запис в сесия
        $_SESSION['app_locale']    = $this->locale;
        $_SESSION['app_language']  = $this->language;
        $_SESSION['app_direction'] = $this->direction;
    }

    public function isValidLocale(string $locale): bool
    {
        foreach ($this->languages as $code => $data) {
            if ($code === 'default') continue;
            $l = is_array($data) ? ($data['locale'] ?? null) : $data;
            if ($l === $locale) return true;
        }
        return false;
    }

    /**
     * Извлича език от поддомейна
     */
    protected function detect(): void
    {
        // 🟩 1. Вземаме default език и локал
        if (isset($this->languages['default']) && is_array($this->languages['default'])) {
            $defaultLang = key($this->languages['default']);       // напр. 'en'
            $defaultLocale = current($this->languages['default']); // напр. 'en_US'
        } else {
            $defaultLang = 'en';
            $defaultLocale = 'en_US';
        }

        $this->language = $defaultLang;
        $this->locale = $defaultLocale;
        $this->direction = 'ltr';

        // 🟩 2. Ако няма хост, приключваме
        if (!$this->host) {
            return;
        }

        // 🟩 3. Разделяме хоста
        $parts = array_values(array_filter(explode('.', $this->host)));

        // махаме 'www', ако е отпред
        if (!empty($parts) && $parts[0] === 'www') {
            array_shift($parts);
        }

        // 🟩 4. Проверяваме поддомейна
        foreach ($parts as $part) {
            if (isset($this->languages[$part])) {
                $langData = $this->languages[$part];

                // Поддържаме както ['locale'=>'xx_XX','dir'=>'rtl'] така и 'xx_XX'
                if (is_array($langData)) {
                    $this->language = $part;
                    $this->locale = $langData['locale'] ?? $defaultLocale;
                    $this->direction = $langData['dir'] ?? 'ltr';
                } else {
                    $this->language = $part;
                    $this->locale = $langData;
                    $this->direction = 'ltr';
                }
                break;
            }
        }
    }

    /**
     * Определя протокола (http/https)
     */
    protected function detectProtocol(): string
    {
        // Приоритет 1: $_SERVER['HTTPS']
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return 'https';
        }

        // При reverse proxy: HTTP_X_FORWARDED_PROTO
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
        }

        // При Cloudflare
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            $data = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (!empty($data['scheme'])) {
                return $data['scheme'];
            }
        }

        // fallback
        return 'http';
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;

        // Обновяваме и language ако намерим съвпадение
        foreach ($this->languages as $code => $data) {
            if ($code === 'default') continue;

            $langLocale = is_array($data) ? ($data['locale'] ?? null) : $data;

            if ($langLocale === $locale) {
                $this->language = $code;
                $this->direction = is_array($data) ? ($data['dir'] ?? 'ltr') : 'ltr';
                break;
            }
        }
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getSubdomain(): ?string
    {
        $parts = explode('.', $this->host);
        return $parts[0] ?? null;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getFullUrl(): string
    {
        return $this->protocol . '://' . $this->host;
    }

    public function isValidLanguage(string $code): bool
    {
        return isset($this->languages[$code]);
    }
}
