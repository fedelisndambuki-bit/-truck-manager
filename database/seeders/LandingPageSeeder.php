<?php

namespace Database\Seeders;

use App\Models\LandingHero;
use App\Models\LandingFeature;
use App\Models\LandingPreview;
use App\Models\LandingCta;
use App\Models\LandingNavigation;
use App\Models\LandingFooter;
use App\Models\LandingSeo;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        // Hero
        LandingHero::create([
            'headline' => 'Smart Fleet, Smarter Business',
            'subheadline' => 'Transform your transport operations with AI-powered fleet intelligence. Manage everything from trucks to drivers, all in one beautiful platform.',
            'primary_button_text' => 'Start Free Trial',
            'primary_button_link' => '/register',
            'secondary_button_text' => 'See Mobile App',
            'secondary_button_link' => '#mobile-app',
            'background_style' => '#2F4F4F',
            'status' => true,
        ]);

        // Features
        $features = [
            [
                'title' => 'Fleet Tracking',
                'description' => 'Real-time GPS tracking with live updates and route optimization for your entire fleet.',
                'icon' => 'M8 7H5a2 2 0 00-2 2v9a1 1 0 001 1h1M17 7h2.293a1 1 0 01.707.293l3.414 3.414A1 1 0 0124 11.414V18a2 2 0 01-2 2h-1M6 18a3 3 0 106 0 3 3 0 00-6 0zm11 0a3 3 0 106 0 3 3 0 00-6 0zM13 13V8a1 1 0 00-1-1H9m4 12V7h1M12 11h2',
                'display_order' => 1,
            ],
            [
                'title' => 'Driver Management',
                'description' => 'Complete driver profiles, performance tracking, payroll and scheduling in one place.',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                'display_order' => 2,
            ],
            [
                'title' => 'Financial Settlements',
                'description' => 'Automated payments, fuel advances and complete financial transparency with full audit trails.',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'display_order' => 3,
            ],
            [
                'title' => 'GPS Integration',
                'description' => 'Advanced geofencing, delivery verification and route deviation alerts automatically.',
                'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
                'display_order' => 4,
            ],
            [
                'title' => 'AI Fleet Insights',
                'description' => 'Predictive analytics to optimize routes, reduce fuel costs and flag maintenance needs early.',
                'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                'display_order' => 5,
            ],
            [
                'title' => 'Automated Invoicing',
                'description' => 'Professional invoices with integrated payment tracking and one-click finance exports.',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'display_order' => 6,
            ],
        ];

        foreach ($features as $feature) {
            LandingFeature::create($feature);
        }

        // Previews
        LandingPreview::create([
            'image' => 'images/dashboard screenshot.png',
            'title' => 'Unified Dashboard',
            'description' => 'Your entire fleet operation in one unified workspace.',
            'display_order' => 1,
        ]);

        // CTA
        LandingCta::create([
            'headline' => 'Ready to Transform Your Fleet?',
            'subtext' => 'Join 10,000+ companies already using Tanaki Hub to drive their business forward.',
            'button_text' => 'Start Free Trial',
            'button_link' => '/register',
            'background_style' => '#1e293b',
            'status' => true,
        ]);

        // Navigation
        $navs = [
            ['title' => 'Features', 'link' => '#features', 'display_order' => 1],
            ['title' => 'Mobile App', 'link' => '#mobile-app', 'display_order' => 2],
            ['title' => 'Pricing', 'link' => '#pricing', 'display_order' => 3],
        ];

        foreach ($navs as $nav) {
            LandingNavigation::create($nav);
        }

        // Footer
        LandingFooter::create([
            'company_description' => 'The smart fleet intelligence platform for modern transport companies. Scale your operations with confidence.',
            'email' => 'contact@tanakihub.com',
            'phone' => '+254 700 000 000',
            'copyright_text' => '© ' . date('Y') . ' Tanaki Hub. All rights reserved.',
        ]);

        // SEO
        LandingSeo::create([
            'meta_title' => 'Tanaki Hub - Smart Fleet Management',
            'meta_description' => 'Transform your transport operations with AI-powered fleet intelligence.',
            'meta_keywords' => 'fleet management, logistics, tracking, transport',
        ]);

        // Testimonials
        $testimonials = [
            [
                'name' => 'John Doe',
                'company' => 'Fast Logistics',
                'review' => 'Tanaki Hub has completely transformed how we track our fleet. The AI insights are a game-changer!',
                'rating' => 5,
                'status' => true,
            ],
            [
                'name' => 'Jane Smith',
                'company' => 'Global Trans',
                'review' => 'The driver management module is intuitive and has saved us countless hours of paperwork.',
                'rating' => 5,
                'status' => true,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            \App\Models\LandingTestimonial::create($testimonial);
        }
    }
}
