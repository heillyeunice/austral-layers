<?php

namespace App\Providers;

use App\Models\BusinessCustomer;
use App\Models\BusinessDocument;
use App\Models\BusinessExpense;
use App\Models\BusinessOrder;
use App\Models\BusinessOrderStageAssignment;
use App\Models\BusinessPayment;
use App\Models\PortfolioProject;
use App\Models\PrinterPreset;
use App\Models\PrintQuotation;
use App\Models\Product;
use App\Models\QuoteInquiry;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\NewQuoteInquiry;
use App\Observers\AuditTrailObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([
            BusinessCustomer::class,
            BusinessDocument::class,
            BusinessExpense::class,
            BusinessOrder::class,
            BusinessOrderStageAssignment::class,
            BusinessPayment::class,
            PrintQuotation::class,
            PrinterPreset::class,
            PortfolioProject::class,
            Product::class,
            QuoteInquiry::class,
            SiteSetting::class,
            User::class,
        ] as $model) {
            $model::observe(AuditTrailObserver::class);
        }

        View::composer(['welcome', 'layouts.app'], function ($view): void {
            $settings = SiteSetting::defaults();

            if (Schema::hasTable('site_settings')) {
                $settings = array_replace(
                    $settings,
                    SiteSetting::query()->pluck('value', 'key')->all()
                );
            }

            $view->with('siteSettings', $settings);
        });

        View::composer('partials.site-header', function ($view): void {
            $notifications = collect();
            $unreadCount = 0;
            $staffUser = null;

            if (! request()->routeIs('admin.login*', 'superadmin.login*')) {
                $user = auth()->user();

                if ($user instanceof User && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPERADMIN], true)) {
                    $staffUser = $user;
                    $unreadCount = $user->unreadNotifications()
                        ->where('type', NewQuoteInquiry::class)
                        ->count();
                    $notifications = $user->unreadNotifications()
                        ->where('type', NewQuoteInquiry::class)
                        ->latest()
                        ->limit(8)
                        ->get();
                }
            }

            $view->with([
                'staffHeaderUser' => $staffUser,
                'staffNotifications' => $notifications,
                'staffUnreadNotificationCount' => $unreadCount,
            ]);
        });
    }
}
