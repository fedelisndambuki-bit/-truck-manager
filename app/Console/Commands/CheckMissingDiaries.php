<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DailySiteDiary;
use App\Models\DiarySetting;
use App\Models\Site;
use App\Notifications\DiaryNotification;
use App\Services\DiaryNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckMissingDiaries extends Command
{
    protected $signature = 'diaries:check-missing';

    protected $description = 'Notify relevant users about active construction sites with no diary logged for yesterday';

    public function handle(DiaryNotificationRecipients $recipients): void
    {
        $yesterday = now()->subDay()->toDateString();
        $flagged = 0;

        Company::query()->each(function (Company $company) use ($yesterday, $recipients, &$flagged) {
            if (! DiarySetting::getSetting($company->id, 'notify_missing_diary', true)) {
                return;
            }

            $sitesWithDiaries = DailySiteDiary::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->whereDate('date', $yesterday)
                ->pluck('site_id');

            $missingSites = Site::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->where('status', 'Active')
                ->whereNotIn('id', $sitesWithDiaries)
                ->get();

            if ($missingSites->isEmpty()) {
                return;
            }

            $users = $recipients->forCompany($company->id);

            if ($users->isEmpty()) {
                return;
            }

            foreach ($missingSites as $site) {
                Notification::send($users, new DiaryNotification('diary_missing', [
                    'site_name' => $site->name,
                ]));
                $flagged++;
            }
        });

        $this->info("Checked for missing diaries on {$yesterday}. Flagged {$flagged} site(s).");
    }
}
