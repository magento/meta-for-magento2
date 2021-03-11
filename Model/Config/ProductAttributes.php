<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 */

namespace Facebook\BusinessExtension\Model\Config;

class ProductAttributes
{
    /**
     * @var array
     */
    protected $attributesConfig = [];

    /**
     * ProductAttributes constructor
     */
    public function __construct()
    {
        $this->setAttributesConfig();
    }

    /**
     * @return string
     */
    public function getAttributeGroupName()
    {
        return 'Facebook Attribute Group';
    }

    public function setAttributesConfig()
    {
        $this->attributesConfig = [
            'facebook_age_group' => [
                'label' => 'Age Group',
                'source' => 'Facebook\BusinessExtension\Model\Config\Source\Product\AgeGroup',
                'code' => 'facebook_age_group',
                'input' => 'select',
                'type' => 'text',
                'sort_order' => 1,
                'note' => null,
            ],
            'facebook_gender' => [
                'label' => 'Gender',
                'source' => 'Facebook\BusinessExtension\Model\Config\Source\Product\Gender',
                'code' => 'facebook_gender',
                'input' => 'select',
                'type' => 'text',
                'sort_order' => 2,
                'note' => null,
            ],
            'facebook_pattern' => [
                'label' => 'Pattern',
                'source' => 'Facebook\BusinessExtension\Model\Config\Source\Product\Pattern',
                'code' => 'facebook_pattern',
                'input' => 'select',
                'type' => 'text',
                'sort_order' => 3,
                'note' => null,
            ],
            'facebook_decor_style' => [
                'label' => 'Decor Style',
                'source' => 'Facebook\BusinessExtension\Model\Config\Source\Product\DecorStyle',
                'code' => 'facebook_decor_style',
                'input' => 'select',
                'type' => 'text',
                'sort_order' => 4,
                'note' => null,
            ],
            'facebook_color' => [
                'label' => 'Color',
                'source' => '',
                'code' => 'facebook_color',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 5,
                'note' => 'Use one or more words to describe the color, not a hex code. "
                            . "Sample Sample value: Royal Blue.',
            ],
            'facebook_capacity' => [
                'label' => 'Capacity',
                'source' => '',
                'code' => 'facebook_capacity',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 6,
                'note' => 'Sample values: 20 lbs, 10 liters, 4.5 cu ft, 12 oz, 8 oz, 1 Litre',
            ],
            'facebook_material' => [
                'label' => 'Material',
                'source' => '',
                'code' => 'facebook_material',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 7,
                'note' => 'Primary material(s) of the item. Sample values: Plastic, Rubber, Cotton.',
            ],
            'facebook_size' => [
                'label' => 'Size',
                'source' => '',
                'code' => 'facebook_size',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 8,
                'note' => 'Sample Values: Small, Medium, Large, \'2\', 4, 6, One Size, Twin, Full, Queen, King',
            ],
            'facebook_style' => [
                'label' => 'Style',
                'source' => '',
                'code' => 'facebook_style',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 9,
                'note' => 'Sample values: bangle, cuff, engagement ring, stud, hoops, Maxi, "
                            ."Boyfriend, Braided, fashion, fine',
            ],
            'facebook_brand' => [
                'label' => 'Brand',
                'source' => '',
                'code' => 'facebook_brand',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 10,
                'note' => 'Brand name, unique manufacturer part number (MPN), or Global Trade "
                            ."Item Number (GTIN) of the item.',
            ],
            'facebook_product_length' => [
                'label' => 'Product Length',
                'source' => '',
                'code' => 'facebook_product_length',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 11,
                'note' => 'Length of the fully assembled product. Sample values: 5 in, 2 ft, 60 cm',
            ],
            'facebook_product_width' => [
                'label' => 'Product Width',
                'source' => '',
                'code' => 'facebook_product_width',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 12,
                'note' => 'Length of the fully assembled product. Sample values: 5 in, 2 ft, 60 cm',
            ],
            'facebook_product_height' => [
                'label' => 'Product Height',
                'source' => '',
                'code' => 'facebook_product_height',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 13,
                'note' => 'Length of the fully assembled product. Sample values: 5 in, 2 ft, 60 cm',
            ],
            'facebook_model' => [
                'label' => 'Model',
                'source' => '',
                'code' => 'facebook_model',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 14,
                'note' => 'Common name of the model of the product. Does not include model numbers. "
                            ."Sample values: iPhone 6, Galaxy S8.',
            ],
            'facebook_product_depth' => [
                'label' => 'Product Depth',
                'source' => '',
                'code' => 'facebook_product_depth',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 15,
                'note' => 'Depth of the fully assembled product. Sample values: 5 in, 2 ft, 60 cm.',
            ],
            'facebook_ingredients' => [
                'label' => 'Ingredients',
                'source' => '',
                'code' => 'facebook_ingredients',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 16,
                'note' => 'List of active ingredients as shown on the item label.',
            ],
            'facebook_resolution' => [
                'label' => 'Resolution',
                'source' => '',
                'code' => 'facebook_resolution',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 17,
                'note' => 'Resolution of the product screen. Sample values: 1080p, 4k, UHD, 24 MP.',
            ],
            'facebook_age_range' => [
                'label' => 'Age Range',
                'source' => '',
                'code' => 'facebook_age_range',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 18,
                'note' => 'Minimum and maximum ages for a product, such as the unit of measure in "
                        ."Months, or Years. Sample values: 1-3 yrs, 6-9 mos.',
            ],
            'facebook_screen_size' => [
                'label' => 'Screen Size',
                'source' => '',
                'code' => 'facebook_screen_size',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 19,
                'note' => 'Measurement of the device\'s screen, typically measured diagonally "
                            ."in inches. Sample values: 42 in, 5.5 in.',
            ],
            'facebook_maximum_weight' => [
                'label' => 'Maximum Weight',
                'source' => '',
                'code' => 'facebook_maximum_weight',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 20,
                'note' => 'Sample values: 35 lb, 45 lb, 15 kg, 20 kg.',
            ],
            'facebook_minimum_weight' => [
                'label' => 'Minimum Weight',
                'source' => '',
                'code' => 'facebook_minimum_weight',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 21,
                'note' => 'Sample values: 35 lb, 45 lb, 15 kg, 20 kg.',
            ],
            'facebook_display_technology' => [
                'label' => 'Display Technology',
                'source' => '',
                'code' => 'facebook_minimum_weight',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 22,
                'note' => 'Type of technology that powers the display. Sample values: Analog, Digital, LED, LCD.',
            ],
            'facebook_operating_system' => [
                'label' => 'Operating System',
                'source' => '',
                'code' => 'facebook_operating_system',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 23,
                'note' => 'Type of preloaded operating system software installed on the device. "
                        ."Sample values: Android, iOS, Windows.',
            ],
            'facebook_is_assembly_required' => [
                'label' => 'Is Assembly Required',
                'source' => 'Facebook\BusinessExtension\Model\Config\Source\Product\Assembly',
                'code' => 'facebook_is_assembly_required',
                'input' => 'select',
                'type' => 'text',
                'sort_order' => 24,
                'note' => null,
            ],
            'facebook_storage_capacity' => [
                'label' => 'Storage Capacity',
                'source' => '',
                'code' => 'facebook_storage_capacity',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 25,
                'note' => 'Amount of storage space on the item\'s hard drive, typically measured "
                            ."in megabytes, gigabytes or terabytes. Sample values: 1 TB, 16 GB.',
            ],
            'facebook_number_of_licenses' => [
                'label' => 'Number of Licenses',
                'source' => '',
                'code' => 'facebook_number_of_licenses',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 26,
                'note' => 'Maximum number of users or installations Sample under the terms of the "
                            ."software licensing agreement. Sample values: 1, 3, 5.',
            ],
            'facebook_product_form' => [
                'label' => 'Product Form',
                'source' => '',
                'code' => 'facebook_product_form',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 27,
                'note' => 'Consistency, texture, or formulation of the item and the way it will be consumed "
                            ."or dispensed. Sample values: Oil, Gel, Spray, Cream, Powder, Serum, Liquid.',
            ],
            'facebook_compatible_devices' => [
                'label' => 'Compatible Devices',
                'source' => '',
                'code' => 'facebook_compatible_devices',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 28,
                'note' => 'Devices compatible with the item. Sample values: iPad, Tablet Computers, "
                            ."Windows Desktop Computers, Apple Computers.',
            ],
            'facebook_video_game_platform' => [
                'label' => 'Video Game Platform',
                'source' => '',
                'code' => 'facebook_video_game_platform',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 29,
                'note' => 'Type of platform on which video game software is capable of running. Sample values:"
                        ."Xbox 360, Nintendo Wii, PC.',
            ],
            'facebook_system_requirements' => [
                'label' => 'Software System Requirements',
                'source' => '',
                'code' => 'facebook_system_requirements',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 30,
                'note' => 'Sample values: Windows 7 or later, Intel Core 2 Duo 1.8 Ghz, 15 GB Free Hard Drive Space.',
            ],
            'facebook_baby_food_stage' => [
                'label' => 'Baby Food Stage',
                'source' => '',
                'code' => 'facebook_baby_food_stage',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 31,
                'note' => 'Sample values: Stage 1, Stage 2, Stage 3, Toddler Food.',
            ],
            'facebook_recommended_use' => [
                'label' => 'Recommended Use',
                'source' => '',
                'code' => 'facebook_recommended_use',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 32,
                'note' => 'Recommended use or surface of cleaning product. Multiple values accepted. "
                            ".Sample values: carpet, hardwood, tile, glass, porcelain, leather.',
            ],
            'facebook_digital_zoom' => [
                'label' => 'Digital Zoom',
                'source' => '',
                'code' => 'facebook_digital_zoom',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 33,
                'note' => 'Magnification power provided by a feature that electronically enlarges the image area."
                 ."Sample values must be numbers (containing only numerals or a decimal point). "
                 ."Sample values: 6x, 160x, 200x. Values such as 20X or 20.4 MP will be rejected.',
            ],
            'facebook_scent' => [
                'label' => 'Scent',
                'source' => '',
                'code' => 'facebook_scent',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 34,
                'note' => 'Scent(s) or fragrance(s) of your item, including items labeled as "unscented". Multiple".
                            "values accepted. Sample values: Lavender, Vanilla, Lemon, Coconut, Jasmine, Pine.',
            ],
            'facebook_health_concern' => [
                'label' => 'Health Concern',
                'source' => '',
                'code' => 'facebook_health_concern',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 35,
                'note' => 'Multiple values accepted. Sample values: Fever, Allergies, Cholesterol, Blood Sugar.',
            ],
            'facebook_megapixels' => [
                'label' => 'Megapixels',
                'source' => '',
                'code' => 'facebook_megapixels',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 36,
                'note' => 'Resolution at which this item records images. Sample values: 16.0 MP, 24.2 MP.',
            ],
            'facebook_thread_count' => [
                'label' => 'Thread Count',
                'source' => '',
                'code' => 'facebook_thread_count',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 37,
                'note' => 'Number of threads per square inch of fabric. Sample values: 400, 600, 1000.',
            ],
            'facebook_gemstone' => [
                'label' => 'Gemstone',
                'source' => '',
                'code' => 'facebook_gemstone',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 38,
                'note' => 'Type of gemstone(s) in your item. Sample values: Diamond, "
                        ."Turquoise, Ruby, Emerald, Sapphire.',
            ],
            'facebook_optical_zoom' => [
                'label' => 'Optical Zoom',
                'source' => '',
                'code' => 'facebook_optical_zoom',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 39,
                'note' => 'Magnification power of a physical optical zoom lens. Sample values: 10x, 20x, 24x.',
            ],
            'facebook_package_quantity' => [
                'label' => 'Package Quantity',
                'source' => '',
                'code' => 'facebook_package_quantity',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 40,
                'note' => 'Total number of items included in the package or box. Sample values: 12, 24, 36.',
            ],
            'facebook_shoe_width' => [
                'label' => 'Shoe Width',
                'source' => '',
                'code' => 'facebook_shoe_width',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 41,
                'note' => 'Width of shoes. Sample values: A, B, EE, Narrow, Wide.',
            ],
            'facebook_finish' => [
                'label' => 'Finish',
                'source' => '',
                'code' => 'facebook_finish',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 42,
                'note' => 'External treatment to the product that usually includes a change in appearance or" .
                           "texture to the item. Commonly used for furniture include wood, metal, and fabric. Sample ".
                           ".values: Natural/Unfinished, Walnut, Pewter, Antiqued.',
            ],
            'facebook_product_weight' => [
                'label' => 'Product Weight',
                'source' => '',
                'code' => 'facebook_product_weight',
                'input' => 'text',
                'type' => 'text',
                'sort_order' => 43,
                'note' => 'Weight of the fully assembled product. Sample values: 45 lb, 120 lb, 54 kg, 80 kg.',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getAttributesConfig()
    {
        return $this->attributesConfig;
    }
}
