<?php

/**
 * Demo seafood catalogue for a B2B processor / exporter: category -> species -> cut -> product.
 *
 * IMPORTANT: this is sample data. A species being listed here, or suitable for sashimi, does NOT
 * mean the company sells it: what is sold is decided per product (products.sashimi_grade) and per
 * cut (cut_species.available_as_product). Every row is seeded with is_sample = true ("Contoh" in
 * the admin) until someone saves it there. Shelf life, packaging, temperatures and origins are
 * typical industry values for illustration, not claims about a real supplier: confirm them before
 * launch. No certification is listed on any product.
 *
 * Photos: see catalog-images.json (Wikimedia Commons, with licence and author). Their licences
 * (CC BY / CC BY-SA need attribution) and their relevance must be reviewed, and they replaced by
 * the company's own product photography, before production. image_is_reference = the photo shows
 * the species or the finished dish, not this exact cut.
 */

return [
    'categories' => [
        // slug, name, description, colour, image key
        ['tuna', 'Tuna', 'Yellowfin, bluefin and bigeye tuna in whole, loin, saku, steak and premium sashimi cuts.', '#C9553D', 'sp-yellowfin-tuna'],
        ['salmon', 'Salmon', 'Atlantic salmon as whole fish, fillet, saku, belly and steak for sashimi, sushi and foodservice.', '#E58A5B', 'sp-atlantic-salmon'],
        ['mackerel', 'Mackerel', 'Spanish mackerel as fillet and saku for grilling, searing and sashimi.', '#5B8DB8', 'sp-spanish-mackerel'],
        ['snapper', 'Snapper', 'Red snapper as whole fish and fillet.', '#D9534F', 'sp-red-snapper'],
        ['yellowtail', 'Yellowtail', 'Hamachi (yellowtail) as loin, saku and fillet for sashimi and sushi.', '#D9B26F', 'sp-hamachi'],
        ['sea-bream', 'Sea Bream', 'Tai (red sea bream) as whole fish, fillet and saku.', '#C48B9F', 'sp-tai'],
        ['other-sashimi-fish', 'Other Sashimi Fish', 'Other species commonly served as sashimi, such as kanpachi.', '#7CC4E4', 'sp-kanpachi'],
    ],

    'processing_methods' => [
        // slug, name, type, temperature, description
        ['fresh', 'Fresh', 'freshness', '0 to 4°C', 'Chilled, never frozen. Kept on ice or in a chiller.'],
        ['frozen', 'Frozen', 'freshness', '-18°C or below', 'Frozen and stored at standard cold-storage temperature.'],
        ['super-frozen', 'Super Frozen', 'freshness', '-60°C', 'Ultra-low temperature freezing designed to preserve texture, color and freshness after thawing.'],
        ['skinless', 'Skinless', 'skin', null, 'Skin removed.'],
        ['skin-on', 'Skin-on', 'skin', null, 'Skin left on the flesh.'],
        ['boneless', 'Boneless', 'bone', null, 'Bones and pin bones removed.'],
        ['trimmed', 'Trimmed', 'trim', null, 'Trimmed of blood line, sinew and dark meat.'],
        ['co-treated', 'CO Treated', 'treatment', null, 'Treated with carbon monoxide to keep the red color of the flesh. Declare the treatment where the destination market requires it.'],
    ],

    // slug, name, body region, description
    'cuts' => [
        ['whole-round', 'Whole / Round', 'Whole fish', 'The whole fish, gilled and gutted or round, before any cutting.'],
        ['head', 'Head', 'Head', 'The head of the fish.'],
        ['collar', 'Collar', 'Collar', 'The collar behind the gills, popular grilled.'],
        ['loin', 'Loin', 'Back (loin)', 'A long boneless cut from the back of the fish.'],
        ['back-loin', 'Back Loin', 'Back (loin)', 'The loin taken from the upper back, leaner than the belly side.'],
        ['belly', 'Belly', 'Belly', 'The fatty belly section of the fish.'],
        ['akami', 'Akami', 'Back / lean muscle', 'The lean red muscle of tuna, taken from the back.'],
        ['chutoro', 'Chutoro', 'Belly / mid-fatty section', 'The mid-fatty section of tuna between akami and otoro.'],
        ['otoro', 'Otoro', 'Belly (fattiest section)', 'The fattiest, most marbled part of tuna belly.'],
        ['cheek', 'Cheek', 'Head (cheek)', 'The cheek meat from the head.'],
        ['tail', 'Tail', 'Tail', 'The tail section.'],
        ['fillet', 'Fillet', 'Side of fish', 'A side of the fish removed from the backbone.'],
        ['saku', 'Saku', 'Loin / fillet block', 'A rectangular block cut from the loin or fillet, ready to slice for sashimi and sushi.'],
        ['sashimi-block', 'Sashimi Block', 'Fillet block', 'A trimmed block prepared for slicing sashimi.'],
        ['steak', 'Steak', 'Cross-cut', 'A portion cut across the loin or body.'],
        ['cube-poke', 'Cube / Poke', 'Trim from loin', 'Diced cubes for poke, tartare and salads.'],
        ['strip-meat', 'Strip Meat', 'Scraped / trim meat', 'Meat scraped or trimmed from the bones and frames, used in rolls and ground preparations.'],
    ],

    // slug, category, product name prefix, common name, scientific, japanese, short description, origin (species range), habitat,
    // is_sashimi_suitable, colour, flavour, image key, cuts [cut slug => available as product]
    'species' => [
        ['yellowfin-tuna', 'tuna', 'Yellowfin Tuna', 'Yellowfin Tuna', 'Thunnus albacares', 'Kihada Maguro',
            'A fast-growing tropical tuna with firm, bright red-pink flesh and a clean, mild flavor, widely used for sashimi, sushi and poke.',
            'Tropical and subtropical oceans worldwide, including Indonesian waters', 'Open ocean, mostly in the upper 100 m of tropical and subtropical waters.',
            true, 'Bright red to pink', 'Mild, clean', 'sp-yellowfin-tuna',
            ['whole-round' => true, 'head' => false, 'collar' => false, 'loin' => true, 'back-loin' => false, 'belly' => false, 'saku' => true, 'steak' => true, 'cube-poke' => true, 'strip-meat' => true]],
        ['bluefin-tuna', 'tuna', 'Bluefin Tuna', 'Bluefin Tuna', 'Thunnus thynnus', 'Honmaguro',
            'The largest tuna and the most prized for sushi, valued for deep red flesh and richly marbled belly cuts: akami, chutoro and otoro.',
            'Atlantic Ocean and Mediterranean Sea', 'Temperate open ocean, migrating across the Atlantic.',
            true, 'Deep red', 'Rich, buttery in the fatty cuts', 'sp-bluefin-tuna',
            ['whole-round' => false, 'head' => false, 'collar' => false, 'cheek' => false, 'tail' => false, 'belly' => false, 'loin' => true, 'akami' => true, 'chutoro' => true, 'otoro' => true, 'saku' => true]],
        ['bigeye-tuna', 'tuna', 'Bigeye Tuna', 'Bigeye Tuna', 'Thunnus obesus', 'Mebachi Maguro',
            'A tuna with deep red flesh and a slightly higher fat content than yellowfin, popular for sashimi and sushi.',
            'Tropical and temperate oceans worldwide', 'Open ocean, feeding at greater depths than yellowfin.',
            true, 'Deep red', 'Rich, slightly sweet', 'sp-bigeye-tuna',
            ['whole-round' => true, 'loin' => true, 'saku' => true, 'steak' => true, 'akami' => false]],
        ['atlantic-salmon', 'salmon', 'Atlantic Salmon', 'Atlantic Salmon', 'Salmo salar', 'Sake',
            'A fatty, orange-fleshed salmon with a soft texture and mild sweet flavor, available year-round from aquaculture.',
            'North Atlantic; commercially farmed in Norway, Chile, Scotland and Canada', 'Anadromous: spends its adult life at sea and returns to rivers to spawn. Farmed in sea cages.',
            true, 'Orange to deep orange', 'Mild, buttery, slightly sweet', 'sp-atlantic-salmon',
            ['whole-round' => true, 'fillet' => true, 'saku' => true, 'sashimi-block' => false, 'steak' => true, 'belly' => true]],
        ['hamachi', 'yellowtail', 'Hamachi', 'Hamachi (Yellowtail)', 'Seriola quinqueradiata', 'Hamachi',
            'Japanese amberjack, known as hamachi when young and buri when mature: rich, lightly sweet flesh with a clean finish.',
            'Northwest Pacific; widely farmed in Japan', 'Coastal and offshore waters of the northwest Pacific.',
            true, 'Pale pink with a darker blood line', 'Rich, lightly sweet', 'sp-hamachi',
            ['whole-round' => false, 'loin' => true, 'saku' => true, 'fillet' => true, 'belly' => false, 'collar' => false]],
        ['tai', 'sea-bream', 'Tai', 'Tai (Red Sea Bream)', 'Pagrus major', 'Tai',
            'The red sea bream of Japanese cuisine: firm, translucent white flesh with a delicate, slightly sweet flavor, often served as sashimi.',
            'Northwest Pacific; widely farmed in Japan', 'Rocky and sandy coastal waters of the northwest Pacific.',
            true, 'Translucent white to pale pink', 'Delicate, lightly sweet', 'sp-tai',
            ['whole-round' => true, 'fillet' => true, 'saku' => true, 'head' => false]],
        ['spanish-mackerel', 'mackerel', 'Spanish Mackerel', 'Spanish Mackerel', 'Scomberomorus commerson', 'Yokoshima-sawara',
            'A large, fast mackerel with firm, pale pink flesh, commonly grilled, seared or served as sashimi when very fresh.',
            'Indo-Pacific, including Indonesian waters', 'Coastal pelagic waters, often near reefs.',
            true, 'Pale pink to light grey', 'Savory, moderately oily', 'sp-spanish-mackerel',
            ['fillet' => true, 'saku' => true, 'whole-round' => false]],
        ['red-snapper', 'snapper', 'Red Snapper', 'Red Snapper', 'Lutjanus campechanus', null,
            'A reef-associated snapper with firm, white-pink flesh and a mild, slightly sweet flavor, sold whole and as fillets.',
            'Western Atlantic and Gulf of Mexico (species range)', 'Rocky reefs and structures on the continental shelf.',
            true, 'White to light pink', 'Mild, slightly sweet', 'sp-red-snapper',
            ['whole-round' => true, 'fillet' => true, 'saku' => false]],
        ['kanpachi', 'other-sashimi-fish', 'Kanpachi', 'Kanpachi (Greater Amberjack)', 'Seriola dumerili', 'Kanpachi',
            'Greater amberjack, valued in sushi bars for its firm, crisp texture and clean, lightly fatty flavor.',
            'Tropical and subtropical oceans worldwide; farmed in Japan', 'Offshore reefs and open water in warm seas.',
            true, 'Pale pink', 'Clean, lightly fatty', 'sp-kanpachi',
            ['whole-round' => true, 'fillet' => false, 'saku' => false]],
    ],

    // Per cut: product-name suffix, code, texture, typical usage, packaging, sentence (use {s} for the species)
    'cut_info' => [
        'whole-round' => ['Whole', 'WHL', 'Firm flesh under intact skin', 'Further processing, filleting, retail and foodservice', 'Glazed and wrapped, bulk or in cartons', 'The whole {s}, supplied for further processing, filleting or whole-fish service.'],
        'loin' => ['Loin', 'LOIN', 'Firm, smooth, fine grain', 'Sashimi, sushi, poke, tataki', 'Individually vacuum packed, master carton', 'Trimmed {s} loin cut from the back of the fish, for slicing into sashimi and sushi or dicing for poke.'],
        'saku' => ['Saku', 'SAKU', 'Firm, smooth, easy to slice', 'Sashimi, sushi', 'Vacuum packed blocks, master carton', 'Rectangular {s} saku block cut from the loin, ready to slice for sashimi and sushi.'],
        'steak' => ['Steak', 'STK', 'Firm, meaty', 'Grilling, seared preparations, restaurant service', 'Individually quick frozen portions, interleaved, master carton', 'Portion-cut {s} steak for searing, grilling and restaurant menus.'],
        'cube-poke' => ['Cube / Poke', 'CUBE', 'Firm, tender when diced', 'Poke, tartare, salads, rice bowls', 'Vacuum packed bags, master carton', 'Diced {s} cubes for poke bowls, tartare and salads.'],
        'strip-meat' => ['Strip Meat', 'STRP', 'Soft, fine, easy to mix', 'Sushi rolls, spicy tuna preparations, fillings', 'Vacuum packed bags, master carton', '{s} meat scraped from the bones and trimmings, used in rolls and other mixed preparations.'],
        'akami' => ['Akami', 'AKM', 'Lean, firm, slightly springy', 'Sashimi, sushi', 'Vacuum packed blocks, master carton', 'Akami, the lean red muscle of {s}, for classic sashimi and sushi.'],
        'chutoro' => ['Chutoro', 'CHU', 'Tender with fine marbling', 'Premium sashimi, sushi', 'Vacuum packed blocks, master carton', 'Chutoro, the mid-fatty belly section of {s}, between lean akami and rich otoro.'],
        'otoro' => ['Otoro', 'OTO', 'Very tender, richly marbled, melts on the palate', 'Premium sashimi, sushi', 'Vacuum packed blocks, master carton', 'Otoro, the fattiest and most marbled part of {s} belly, for premium sashimi and sushi.'],
        'fillet' => ['Fillet', 'FIL', 'Tender, flaking when cooked', 'Sashimi, sushi, grilling, pan-frying', 'Layer packed or vacuum packed, master carton', '{s} fillet removed from the backbone, for sashimi, sushi and cooked dishes.'],
        'belly' => ['Belly', 'BEL', 'Soft, fatty, melts on the palate', 'Sashimi, sushi, grilling', 'Vacuum packed, master carton', '{s} belly, the fatty underside of the fish, for sushi, sashimi and grilling.'],
    ],

    // species slug, cut slug, process methods, freezing key, grade, featured, image key, image is a reference, gallery image keys
    'products' => [
        ['yellowfin-tuna', 'whole-round', ['super-frozen'], 'super_frozen', 'Sashimi Suitable', false, 'p-yft-whole', false, ['sp-yellowfin-tuna', 'p-yft-whole']],
        ['yellowfin-tuna', 'loin', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', true, 'p-tuna-loin', true, ['sp-yellowfin-tuna', 'p-tuna-loin', 'p-yft-pack', 'p-tuna-seared']],
        ['yellowfin-tuna', 'saku', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', true, 'p-tuna-saku', true, ['sp-yellowfin-tuna', 'p-tuna-saku', 'p-yft-pack']],
        ['yellowfin-tuna', 'steak', ['super-frozen', 'skinless', 'boneless'], 'super_frozen', 'General Seafood', false, 'p-tuna-steak', true, ['p-tuna-steak', 'p-tuna-seared']],
        ['yellowfin-tuna', 'cube-poke', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Suitable', false, 'p-tuna-cube', true, []],
        ['yellowfin-tuna', 'strip-meat', ['super-frozen', 'skinless', 'boneless'], 'super_frozen', 'Sushi Grade', false, 'p-yft-strip', false, ['p-yft-strip', 'p-yft-pack']],
        ['bluefin-tuna', 'loin', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', false, 'p-bft-loin', false, ['sp-bluefin-tuna', 'p-bft-loin']],
        ['bluefin-tuna', 'akami', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', false, 'p-akami', true, []],
        ['bluefin-tuna', 'chutoro', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', true, 'p-chutoro', true, []],
        ['bluefin-tuna', 'otoro', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', true, 'p-otoro', true, []],
        ['bluefin-tuna', 'saku', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', false, 'p-bft-saku', true, []],
        ['bigeye-tuna', 'whole-round', ['super-frozen'], 'super_frozen', 'Sashimi Suitable', false, 'p-bet-whole', false, ['sp-bigeye-tuna', 'p-bet-whole']],
        ['bigeye-tuna', 'loin', ['super-frozen', 'skinless', 'boneless', 'trimmed', 'co-treated'], 'super_frozen', 'Sashimi Grade', false, 'p-tuna-loin', true, []],
        ['bigeye-tuna', 'saku', ['super-frozen', 'skinless', 'boneless', 'trimmed'], 'super_frozen', 'Sashimi Grade', false, 'p-tuna-saku', true, []],
        ['bigeye-tuna', 'steak', ['super-frozen', 'skinless', 'boneless'], 'super_frozen', 'General Seafood', false, 'p-tuna-steak', true, []],
        ['atlantic-salmon', 'whole-round', ['fresh'], 'fresh', 'Sashimi Suitable', false, 'p-salmon-whole', false, ['sp-atlantic-salmon', 'p-salmon-whole']],
        ['atlantic-salmon', 'fillet', ['fresh', 'skin-on', 'boneless'], 'fresh', 'Sushi Grade', false, 'p-salmon-fillet', false, ['p-salmon-fillet', 'p-salmon-pack']],
        ['atlantic-salmon', 'saku', ['frozen', 'skinless', 'boneless', 'trimmed'], 'frozen', 'Sashimi Grade', true, 'p-salmon-saku', false, ['sp-atlantic-salmon', 'p-salmon-fillet', 'p-salmon-saku', 'p-salmon-pack']],
        ['atlantic-salmon', 'belly', ['fresh', 'skin-on', 'boneless'], 'fresh', 'Sushi Grade', false, 'p-salmon-fillet', true, []],
        ['atlantic-salmon', 'steak', ['fresh', 'skin-on'], 'fresh', 'General Seafood', false, 'p-salmon-steak', false, ['p-salmon-steak']],
        ['hamachi', 'loin', ['frozen', 'skinless', 'boneless', 'trimmed'], 'frozen', 'Sashimi Grade', false, 'p-hamachi-whole', true, []],
        ['hamachi', 'saku', ['frozen', 'skinless', 'boneless', 'trimmed'], 'frozen', 'Sashimi Grade', false, 'p-hamachi-saku', true, []],
        ['hamachi', 'fillet', ['frozen', 'skin-on', 'boneless'], 'frozen', 'Sashimi Suitable', false, 'p-hamachi-fillet', true, []],
        ['tai', 'whole-round', ['fresh'], 'fresh', 'Sashimi Suitable', false, 'p-tai-whole', false, ['sp-tai', 'p-tai-whole']],
        ['tai', 'fillet', ['fresh', 'skin-on', 'boneless'], 'fresh', 'Sashimi Suitable', false, 'p-tai-fillet', true, []],
        ['tai', 'saku', ['frozen', 'skinless', 'boneless', 'trimmed'], 'frozen', 'Sashimi Grade', false, 'p-tai-saku', true, []],
        ['spanish-mackerel', 'fillet', ['frozen', 'skin-on', 'boneless'], 'frozen', 'General Seafood', false, 'p-mackerel-fillet', true, []],
        ['spanish-mackerel', 'saku', ['frozen', 'skinless', 'boneless', 'trimmed'], 'frozen', 'Sashimi Suitable', false, 'p-mackerel-saku', true, []],
        ['red-snapper', 'whole-round', ['frozen'], 'frozen', 'General Seafood', false, 'p-snapper-whole', false, ['sp-red-snapper', 'p-snapper-whole']],
        ['red-snapper', 'fillet', ['frozen', 'skin-on', 'boneless'], 'frozen', 'Sashimi Suitable', false, 'p-snapper-fillet', false, ['p-snapper-fillet']],
        ['kanpachi', 'whole-round', ['fresh'], 'fresh', 'Sashimi Suitable', false, 'p-kanpachi-whole', false, ['sp-kanpachi', 'p-kanpachi-whole']],
    ],

    // Species abbreviations for product codes (ABC-CUT-001)
    'codes' => [
        'yellowfin-tuna' => 'YFT', 'bluefin-tuna' => 'BFT', 'bigeye-tuna' => 'BET', 'atlantic-salmon' => 'ATS', 'hamachi' => 'HMC',
        'tai' => 'TAI', 'spanish-mackerel' => 'SPM', 'red-snapper' => 'RSN', 'kanpachi' => 'KNP',
    ],

    // Shelf life by freezing key (typical, to be confirmed per product)
    'shelf_life' => [
        'fresh' => '5 to 7 days at 0 to 4°C',
        'frozen' => '12 months at -18°C or below',
        'super_frozen' => '12 months at -60°C',
    ],
];
