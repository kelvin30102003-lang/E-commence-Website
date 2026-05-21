<?php

return [
    // Get these from Supabase Dashboard > Project Settings > Database > Connection string.
    // For XAMPP/local PHP, the Session Pooler is usually easiest because it supports IPv4.
    'host' => 'aws-0-your-region.pooler.supabase.com',
    'port' => '5432',
    'database' => 'postgres',
    'user' => 'postgres.your-project-ref',
    'password' => 'your-database-password',
];
