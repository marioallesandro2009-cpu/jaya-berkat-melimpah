<?php

namespace App\Console\Commands;

use App\Models\ContactMessage;
use App\Support\SecurityLog;
use Illuminate\Console\Command;

/**
 * Retention for the leads (personal data), run daily by the scheduler (bootstrap/app.php):
 *
 * - after LEADS_ANONYMIZE_AFTER_DAYS (default 60) the IP address and the browser string are
 *   erased (they only help against spam and abuse, and are useless later);
 * - after LEADS_DELETE_AFTER_MONTHS (default 12) the whole message is deleted.
 *
 * 0 switches a step off. Backups keep older copies: see DEPLOY.md, "Backup dan pemulihan".
 */
class PruneLeads extends Command
{
    protected $signature = 'leads:prune {--dry-run : Only report what would change}';

    protected $description = 'Erase IP/user-agent of old leads and delete leads past the retention period';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $months = (int) config('site.leads.delete_after_months');
        $days = (int) config('site.leads.anonymize_after_days');

        $deleteQuery = ContactMessage::query()->where('created_at', '<', now()->subMonthsNoOverflow(max($months, 1)));
        $deleted = $months > 0 ? $deleteQuery->count() : 0;

        if ($months > 0 && ! $dryRun) {
            $deleteQuery->delete();
        }

        $anonymizeQuery = ContactMessage::query()
            ->where('created_at', '<', now()->subDays(max($days, 1)))
            // What the delete step removes is not worth anonymising first (and --dry-run must report the same counts).
            ->when($months > 0, fn ($query) => $query->where('created_at', '>=', now()->subMonthsNoOverflow($months)))
            ->where(fn ($query) => $query->whereNotNull('ip')->orWhereNotNull('user_agent'));
        $anonymized = $days > 0 ? $anonymizeQuery->count() : 0;

        if ($days > 0 && ! $dryRun) {
            $anonymizeQuery->update(['ip' => null, 'user_agent' => null]);
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Dihapus: {$deleted}, IP/user-agent dihapus: {$anonymized}.");

        if (! $dryRun && ($deleted > 0 || $anonymized > 0)) {
            SecurityLog::event('leads.pruned', ['deleted' => $deleted, 'anonymized' => $anonymized]);
        }

        return self::SUCCESS;
    }
}
