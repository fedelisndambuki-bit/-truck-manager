<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;

class CurrencySwitcher extends Component
{
    public array $currencies = [
        'KES' => ['label' => 'KES', 'symbol' => 'KSh'],
        'USD' => ['label' => 'USD', 'symbol' => '$'],
        'EUR' => ['label' => 'EUR', 'symbol' => '€'],
        'GBP' => ['label' => 'GBP', 'symbol' => '£'],
    ];

    public string $currentCurrency;

    public function mount()
    {
        $this->currentCurrency = Session::get('currency', 'KES');
    }

    public function setCurrency(string $currency)
    {
        if (array_key_exists($currency, $this->currencies)) {
            $this->currentCurrency = $currency;
            Session::put('currency', $currency);
            
            // Save to platform settings for persistence across sessions and API
            \App\Models\PlatformSetting::updateOrCreate(
                ['key' => 'default_currency'],
                ['value' => $currency, 'type' => 'string', 'group' => 'system']
            );

            $this->dispatch('currency-changed', currency: $currency);
            return redirect(request()->header('Referer'));
        }
    }

    public function render()
    {
        return view('livewire.currency-switcher');
    }
}
