<?php
/**
 * SEO Configuration for Big Deal Ventures
 * Centralized SEO settings and meta data management
 */

class SEOConfig {
    private static $baseUrl = 'https://bigdeal.property';
    private static $siteName = 'Big Deal Ventures';
    private static $defaultImage = '/assets/images/logo.png';
    
    // SEO Meta Data for different pages
    private static $pageData = [
        'home' => [
            'title' => 'Best Real Estate Agency in Mangaluru | Properties for Sale & Rent in Mangalore',
            'description' => 'Leading real estate agency in Mangaluru offering premium apartments, villas, plots, and commercial properties for sale and rent. Expert property consultation in Mangalore.',
            'keywords' => 'real estate Mangaluru, properties Mangalore, apartments for sale Mangaluru, houses for rent Mangalore, property dealers Mangaluru, flats Mangalore, plots Mangaluru, commercial properties Mangalore',
            'canonical' => '/',
            'og_type' => 'website'
        ],
        'about' => [
            'title' => 'Best Real Estate Agency in Mangaluru for Buying and Renting | Real Estate Mangalore',
            'description' => 'Real estate agency in Mangaluru | Property Mangalore | Apartments Mangaluru | Houses for sale in Mangaluru | Houses for sale in Mangalore',
            'keywords' => 'real estate agency Mangaluru, property Mangalore, apartments Mangaluru, houses for sale Mangaluru, property dealers Mangalore, real estate services Mangaluru',
            'canonical' => '/about/',
            'og_type' => 'website'
        ],
        'blog' => [
            'title' => 'Affordable 2 BHK Flats for Rent in Mangaluru | Best Property in Mangalore',
            'description' => 'Luxury 3 BHK apartments for sale in Mangaluru | Apartments for rent in Mangaluru | Property dealers in Mangaluru | Flats for sale in Mangaluru | Affordable plots for sale in Mangaluru near city center',
            'keywords' => '2 BHK flats rent Mangaluru, 3 BHK apartments sale Mangalore, apartments rent Mangaluru, property dealers Mangalore, flats sale Mangaluru, plots sale Mangaluru',
            'canonical' => '/blog/',
            'og_type' => 'website'
        ],
        'services' => [
            'title' => 'Best Residential Property Investment in Mangaluru',
            'description' => 'Affordable houses for sale in Mangaluru under 50 lakhs | Flats Mangaluru | Plots Mangaluru | Houses Mangaluru | Plots for sale in Mangaluru',
            'keywords' => 'residential property investment Mangaluru, houses sale Mangalore under 50 lakhs, flats Mangaluru, plots Mangalore, houses Mangaluru, property investment Mangalore',
            'canonical' => '/services/',
            'og_type' => 'website'
        ],
        'products_buy' => [
            'title' => 'Gated Community Flats for Sale in Mangaluru',
            'description' => 'Commercial office space for rent in Mangaluru | Independent houses for sale in Mangaluru | Beachside villas for sale in Mangaluru | Plots for sale in Mangaluru | Villas Mangaluru',
            'keywords' => 'gated community flats sale Mangaluru, commercial office space rent Mangalore, independent houses sale Mangaluru, beachside villas sale Mangalore, plots sale Mangaluru, villas Mangalore',
            'canonical' => '/products/?listing=Buy',
            'og_type' => 'website'
        ],
        'products_rent' => [
            'title' => 'Best Real Estate Agency in Mangaluru for Buying and Renting',
            'description' => 'Apartments for rent in Mangaluru | Affordable flats in Mangaluru | Apartments Mangaluru | Flats Mangaluru | Property dealers in Mangaluru',
            'keywords' => 'real estate agency Mangaluru, apartments rent Mangalore, affordable flats Mangaluru, apartments Mangalore, flats Mangaluru, property dealers Mangalore',
            'canonical' => '/products/?listing=Rent',
            'og_type' => 'website'
        ],
        'contact' => [
            'title' => 'Best Real Estate Agency in Mangaluru for Buying and Renting',
            'description' => 'Affordable flats in Mangaluru | Commercial space in Mangaluru | Villas for sale in Mangaluru | Affordable 2 BHK flats for rent in Mangaluru | Apartments Mangaluru',
            'keywords' => 'real estate agency Mangaluru, affordable flats Mangalore, commercial space Mangaluru, villas sale Mangalore, 2 BHK flats rent Mangaluru, apartments Mangalore',
            'canonical' => '/contact/',
            'og_type' => 'website'
        ]
    ];
    
    /**
     * Get SEO data for a specific page
     */
    public static function getPageData($pageKey) {
        return isset(self::$pageData[$pageKey]) ? self::$pageData[$pageKey] : self::$pageData['home'];
    }
    
    /**
     * Generate comprehensive favicon tags for all browsers and search engines
     */
    public static function generateFaviconTags() {
        // Use root-relative paths so they work in both local (XAMPP) and production
        // CRITICAL: Google/Bing search engines check /favicon.ico first - this must be prioritized
        // Using ONLY favicon.ico for consistency - no PNG files needed
        $faviconRootICO = '/favicon.ico'; // Primary favicon for search engines and all browsers
        $html = '';
        
        // Root-level favicon.ico (search engines and browsers check this FIRST - Google/Bing requirement)
        $html .= '<link rel="icon" type="image/x-icon" href="' . $faviconRootICO . '?v=3">' . "\n";
        $html .= '<link rel="shortcut icon" href="' . $faviconRootICO . '?v=3" type="image/x-icon">' . "\n";
        
        // Standard favicon sizes using ICO format (for different contexts)
        $html .= '<link rel="icon" type="image/x-icon" sizes="32x32" href="' . $faviconRootICO . '?v=3">' . "\n";
        $html .= '<link rel="icon" type="image/x-icon" sizes="16x16" href="' . $faviconRootICO . '?v=3">' . "\n";
        $html .= '<link rel="icon" type="image/x-icon" sizes="96x96" href="' . $faviconRootICO . '?v=3">' . "\n";
        $html .= '<link rel="icon" type="image/x-icon" sizes="48x48" href="' . $faviconRootICO . '?v=3">' . "\n";
        
        // Apple Touch Icon (for iOS devices) - using ICO
        $html .= '<link rel="apple-touch-icon" href="' . $faviconRootICO . '?v=3">' . "\n";
        
        // Android Chrome icons (for PWA and Android) - using ICO
        $html .= '<link rel="icon" type="image/x-icon" sizes="192x192" href="' . $faviconRootICO . '?v=3">' . "\n";
        $html .= '<link rel="icon" type="image/x-icon" sizes="512x512" href="' . $faviconRootICO . '?v=3">' . "\n";
        
        // Manifest link (if you have one)
        // $html .= '<link rel="manifest" href="' . self::$baseUrl . '/manifest.json">' . "\n";
        
        // Theme color for mobile browsers
        $html .= '<meta name="theme-color" content="#cc1a1a">' . "\n";
        $html .= '<meta name="msapplication-TileColor" content="#cc1a1a">' . "\n";
        $html .= '<meta name="msapplication-TileImage" content="' . $faviconRootICO . '?v=3">' . "\n";
        
        return $html;
    }
    
    /**
     * Generate meta tags HTML
     */
    public static function generateMetaTags($pageKey, $customData = []) {
        $data = self::getPageData($pageKey);
        $data = array_merge($data, $customData);
        
        $canonical = self::$baseUrl . $data['canonical'];
        $image = self::$baseUrl . (isset($data['image']) ? $data['image'] : self::$defaultImage);
        
        $html = '';
        
        // Favicon tags (must be early in head for search engines)
        $html .= self::generateFaviconTags();
        
        // Basic meta tags
        $html .= '<title>' . htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
        $html .= '<meta name="description" content="' . htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta name="keywords" content="' . htmlspecialchars($data['keywords'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta name="author" content="' . self::$siteName . '">' . "\n";
        $html .= '<meta name="robots" content="index, follow">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        
        // Canonical URL
        $html .= '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        
        // Open Graph tags
        $html .= '<meta property="og:title" content="' . htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta property="og:type" content="' . htmlspecialchars($data['og_type'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta property="og:url" content="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta property="og:image" content="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta property="og:site_name" content="' . self::$siteName . '">' . "\n";
        $html .= '<meta property="og:locale" content="en_IN">' . "\n";
        
        // Twitter Card tags
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8') . '">' . "\n";
        $html .= '<meta name="twitter:image" content="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        
        // Additional SEO tags
        $html .= '<meta name="geo.region" content="IN-KA">' . "\n";
        $html .= '<meta name="geo.placename" content="Mangaluru, Karnataka, India">' . "\n";
        $html .= '<meta name="geo.position" content="12.9141;74.8560">' . "\n";
        $html .= '<meta name="ICBM" content="12.9141, 74.8560">' . "\n";
        
        return $html;
    }
    
    /**
     * Generate JSON-LD structured data for real estate business
     */
    public static function generateStructuredData($pageKey = 'home', $properties = []) {
        $data = [
            "@context" => "https://schema.org",
            "@type" => "RealEstateAgent",
            "name" => "Big Deal Ventures",
            "description" => "Leading real estate agency in Mangaluru offering premium properties for sale and rent",
            "url" => self::$baseUrl,
            "logo" => self::$baseUrl . self::$defaultImage,
            "image" => self::$baseUrl . self::$defaultImage,
            "telephone" => "+91-99018-05505",
            "email" => "office@bigdeal.property",
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "Kankanady",
                "addressLocality" => "Mangaluru",
                "addressRegion" => "Karnataka",
                "postalCode" => "575002",
                "addressCountry" => "IN"
            ],
            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => "12.9141",
                "longitude" => "74.8560"
            ],
            "areaServed" => [
                "@type" => "City",
                "name" => "Mangaluru",
                "containedInPlace" => [
                    "@type" => "State",
                    "name" => "Karnataka"
                ]
            ],
            "serviceType" => [
                "Property Sales",
                "Property Rentals",
                "Property Management",
                "Real Estate Consultation"
            ],
            "priceRange" => "₹₹₹",
            "openingHours" => "Mo-Su 09:00-18:00"
        ];
        
        // Add properties if provided
        if (!empty($properties)) {
            $data["hasOfferCatalog"] = [
                "@type" => "OfferCatalog",
                "name" => "Properties for Sale and Rent",
                "itemListElement" => []
            ];
            
            foreach ($properties as $index => $property) {
                $data["hasOfferCatalog"]["itemListElement"][] = [
                    "@type" => "Offer",
                    "position" => $index + 1,
                    "itemOffered" => [
                        "@type" => "Product",
                        "name" => $property['title'] ?? 'Property',
                        "description" => $property['description'] ?? '',
                        "image" => isset($property['image']) ? self::$baseUrl . $property['image'] : null,
                        "offers" => [
                            "@type" => "Offer",
                            "price" => $property['price'] ?? 0,
                            "priceCurrency" => "INR",
                            "availability" => "https://schema.org/InStock"
                        ]
                    ]
                ];
            }
        }
        
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
    
    /**
     * Generate breadcrumb structured data
     */
    public static function generateBreadcrumbData($breadcrumbs) {
        $data = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => []
        ];
        
        foreach ($breadcrumbs as $index => $crumb) {
            $data["itemListElement"][] = [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => $crumb['name'],
                "item" => isset($crumb['url']) ? self::$baseUrl . $crumb['url'] : null
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
    
    /**
     * Generate FAQ structured data
     */
    public static function generateFAQData($faqs) {
        $data = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => []
        ];
        
        foreach ($faqs as $faq) {
            $data["mainEntity"][] = [
                "@type" => "Question",
                "name" => $faq['question'],
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $faq['answer']
                ]
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
?>
