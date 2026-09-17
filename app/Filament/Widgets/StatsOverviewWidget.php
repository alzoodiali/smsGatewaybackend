<?php

namespace App\Filament\Widgets;

use App\Models\ClientApp;
use App\Models\OtpCode;
use App\Models\SmsGateway;
use App\Models\SmsMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $sentCount = SmsMessage::where('status', 'sent')->count();
        $pendingCount = SmsMessage::where('status', 'pending')->count();
        $onlineGateways = SmsGateway::where('status', 'online')->count();
        $totalGateways = SmsGateway::count();
        $activeOtp = OtpCode::where('is_used', false)->where('expires_at', '>', now())->count();

        return [
            Stat::make('الرسائل المرسلة بنجاح', $sentCount)
                ->description('إجمالي رسائل SMS الناجحة')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('رسائل قيد الانتظار', $pendingCount)
                ->description('بانتظار سحب البوابة')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('البوابات المتصلة', "{$onlineGateways} / {$totalGateways}")
                ->description('أجهزة Android المتصلة بالنظام')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color($onlineGateways > 0 ? 'success' : 'danger'),

            Stat::make('رموز OTP النشطة', $activeOtp)
                ->description('رموز غير مستخدمة وصالحة')
                ->descriptionIcon('heroicon-m-key')
                ->color('info'),
        ];
    }
}
