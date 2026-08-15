<?php

namespace App\AI;

class PromptLibrary
{
    public static function system()
    {
        $path = resource_path('ai/system.txt');
        if (!is_file($path)) {
            return 'You are a helpful assistant for a truck fleet management system.';
        }
        $content = file_get_contents($path);
        return trim($content) !== '' ? $content : 'You are a helpful assistant for a truck fleet management system.';
    }

    public static function fleet()
    {
        return file_get_contents(resource_path('ai/fleet.txt'));
    }

    public static function finance()
    {
        return file_get_contents(resource_path('ai/finance.txt'));
    }
}
