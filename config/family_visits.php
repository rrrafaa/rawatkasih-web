<?php

return [
    'facility_name' => env('FAMILY_FACILITY_NAME', 'Wisma Kasih Utama'),
    'demo_news' => env('FAMILY_DEMO_NEWS', true),
    // Seeded fictional resident UUID; never infer a portrait from a person's name.
    'demo_portrait_resident_id' => env('FAMILY_DEMO_PORTRAIT_RESIDENT_ID', '11111111-1111-4111-8111-111111111111'),
];
