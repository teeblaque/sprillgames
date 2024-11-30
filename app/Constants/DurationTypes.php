<?php

namespace App\Constants;

class DurationTypes
{
    public const ANYTIME = 'anytime';
    public const DAILY = 'daily';
    public const WEEKLY = 'weekly';
    public const MONTHLY = 'monthly';

    public static function frequency()
    {
        return [
            self::ANYTIME,
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY
        ];
    }

    public static function frequencyInDays()
    {
        $frequency = array(
            "daily"=>1,
            "weekly"=>7,
            "monthly"=>30
        );
        return $frequency;
    }

    public static function frequecyList()
    {
        return [
            // ['name' => 'Anytime','val' => self::ANYTIME],
            [
                'name' => 'Daily',
                'val' => self::DAILY,
                'days' => 1,
            ],

            [
                'name' => 'Weekly',
                'val' => self::WEEKLY,
                'days' => 7,
            ],
            [
                'name' => 'Monthly',
                'val' => self::MONTHLY,
                'days' => 30,
            ],
        ];
    }

    public static function durationList()
    {
        return [
            ['name' => '1 month','val' => 30],
            ['name' => '2 months','val' => 60],
            ['name' => '3 months','val' => 90],
            ['name' => '6 months','val' => 180],
            ['name' => '9 months','val' => 270],
            ['name' => '12 months','val' => 365],
            // ['name' => '24 months','val' => 720],
        ];
    }

    public static function productDurationList()
    {
        return [
            ['name' => 'None','val' => 0],
            ['name' => '3 months','val' => 90],
            ['name' => '6 months','val' => 180],
            ['name' => '9 months','val' => 270],
            ['name' => '12 months','val' => 365],
            // ['name' => '24 months','val' => 720],
        ];
    }

    public static function durationListInDays()
    {
        return [
            ['name' => '30 days','val' => 30, 'upper_bound' => 59, 'upper_bound_name' => '59 days'],
            ['name' => '60 days','val' => 60, 'upper_bound' => 89, 'upper_bound_name' => '89 days'],
            ['name' => '90 days','val' => 90, 'upper_bound' => 179, 'upper_bound_name' => '179 days'],
            ['name' => '180 days','val' => 180, 'upper_bound' => 269, 'upper_bound_name' => '269 days'],
            ['name' => '270 days','val' => 270, 'upper_bound' => 364, 'upper_bound_name' => '364 days'],
            ['name' => '365 days','val' => 365, 'upper_bound' => 730, 'upper_bound_name' => '730 days'],
        ];
    }

    public static function duration()
    {
        return [90, 180, 270, 360,720];
    }
}
