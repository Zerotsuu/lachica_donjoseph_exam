<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\User;
use Carbon\Carbon;

class SanctumMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:maintenance 
                          {--prune-expired : Remove expired tokens}
                          {--prune-old : Remove tokens older than specified days}
                          {--days=30 : Number of days for old token cleanup}
                          {--limit-tokens : Enforce max tokens per user limit}
                          {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform maintenance tasks on Sanctum tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Starting Sanctum maintenance...');
        
        $dryRun = $this->option('dry-run');
        $totalCleaned = 0;
        
        if ($this->option('prune-expired')) {
            $totalCleaned += $this->pruneExpiredTokens($dryRun);
        }
        
        if ($this->option('prune-old')) {
            $days = (int) $this->option('days');
            $totalCleaned += $this->pruneOldTokens($days, $dryRun);
        }
        
        if ($this->option('limit-tokens')) {
            $totalCleaned += $this->enforceTokenLimits($dryRun);
        }
        
        // Show token statistics
        $this->showTokenStatistics();
        
        $action = $dryRun ? 'would be cleaned' : 'cleaned';
        $this->info("✅ Maintenance complete! {$totalCleaned} tokens {$action}.");
        
        return 0;
    }
    
    /**
     * Remove expired tokens
     */
    private function pruneExpiredTokens(bool $dryRun = false): int
    {
        $this->info('🗑️  Pruning expired tokens...');
        
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now());
        $count = $expiredTokens->count();
        
        if ($count > 0) {
            if (!$dryRun) {
                $expiredTokens->delete();
            }
            $this->line("   Removed {$count} expired tokens");
        } else {
            $this->line('   No expired tokens found');
        }
        
        return $count;
    }
    
    /**
     * Remove old tokens (based on creation date)
     */
    private function pruneOldTokens(int $days, bool $dryRun = false): int
    {
        $this->info("🗑️  Pruning tokens older than {$days} days...");
        
        $cutoffDate = Carbon::now()->subDays($days);
        $oldTokens = PersonalAccessToken::where('created_at', '<', $cutoffDate);
        $count = $oldTokens->count();
        
        if ($count > 0) {
            if (!$dryRun) {
                $oldTokens->delete();
            }
            $this->line("   Removed {$count} old tokens");
        } else {
            $this->line('   No old tokens found');
        }
        
        return $count;
    }
    
    /**
     * Enforce maximum tokens per user limit
     */
    private function enforceTokenLimits(bool $dryRun = false): int
    {
        $this->info('🔒 Enforcing token limits per user...');
        
        $maxTokens = config('sanctum.security.max_tokens_per_user', 10);
        $totalCleaned = 0;
        
        $users = User::whereHas('tokens')->get()->filter(function ($user) use ($maxTokens) {
            return $user->tokens()->count() > $maxTokens;
        });
        
        if ($users->count() > 0) {
            foreach ($users as $user) {
                $userTokenCount = $user->tokens()->count();
                $tokensToDelete = $user->tokens()
                    ->orderBy('last_used_at', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->skip($maxTokens)
                    ->take($userTokenCount - $maxTokens);
                
                $deleteCount = $tokensToDelete->count();
                
                if ($deleteCount > 0) {
                    if (!$dryRun) {
                        $tokensToDelete->delete();
                    }
                    $this->line("   User {$user->email}: removed {$deleteCount} excess tokens");
                    $totalCleaned += $deleteCount;
                }
            }
        } else {
            $this->line('   All users within token limits');
        }
        
        return $totalCleaned;
    }
    
    /**
     * Show token statistics
     */
    private function showTokenStatistics(): void
    {
        $this->info('📊 Token Statistics:');
        
        $totalTokens = PersonalAccessToken::count();
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', now())->count();
        $activeTokens = $totalTokens - $expiredTokens;
        $tokensUsedToday = PersonalAccessToken::where('last_used_at', '>=', now()->startOfDay())->count();
        $uniqueUsers = PersonalAccessToken::distinct('tokenable_id')->count('tokenable_id');
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tokens', $totalTokens],
                ['Active Tokens', $activeTokens],
                ['Expired Tokens', $expiredTokens],
                ['Used Today', $tokensUsedToday],
                ['Users with Tokens', $uniqueUsers],
            ]
        );
        
        // Top users by token count (SQLite compatible)
        $topUsers = User::select(['id', 'email', 'role'])
            ->whereHas('tokens')
            ->get()
            ->map(function ($user) {
                $user->tokens_count = $user->tokens()->count();
                return $user;
            })
            ->sortByDesc('tokens_count')
            ->take(5);
            
        if ($topUsers->count() > 0) {
            $this->info('👥 Top Users by Token Count:');
            $this->table(
                ['Email', 'Role', 'Token Count'],
                $topUsers->map(function ($user) {
                    return [
                        $user->email,
                        $user->role,
                        $user->tokens_count,
                    ];
                })->toArray()
            );
        }
        
        // Recent activity
        $recentActivity = PersonalAccessToken::with('tokenable')
            ->where('last_used_at', '>=', now()->subHours(24))
            ->orderBy('last_used_at', 'desc')
            ->limit(5)
            ->get();
            
        if ($recentActivity->count() > 0) {
            $this->info('🕐 Recent Token Activity (Last 24h):');
            $this->table(
                ['User', 'Device', 'Last Used'],
                $recentActivity->map(function ($token) {
                    return [
                        $token->tokenable->email ?? 'Unknown',
                        $token->name,
                        $token->last_used_at?->diffForHumans() ?? 'Never',
                    ];
                })->toArray()
            );
        }
    }
} 