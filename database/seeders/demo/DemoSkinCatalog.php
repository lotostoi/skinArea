<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Enums\ItemCategory;
use App\Enums\ItemRarity;

/**
 * Каталог демо-скинов CS2 для витрины. Используется сидером маркета и призов кейсов.
 * Картинки — уже существующие ассеты проекта из public/images/playstore/goods/csgo/weapon/.
 * Один визуал сознательно переиспользуется под разные названия скинов.
 */
final class DemoSkinCatalog
{
    /**
     * @return list<array{
     *   weapon: string,
     *   skin: string,
     *   category: ItemCategory,
     *   rarity: ItemRarity,
     *   base_price: float,
     *   image: string
     * }>
     */
    public static function skins(): array
    {
        return [
            ['weapon' => 'AK-47', 'skin' => 'Redline', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 1899.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Vulcan', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 14800.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Asiimov', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 6200.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Neon Rider', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 5450.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Fire Serpent', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 32500.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Phantom Disruptor', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 780.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Slate', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::MilSpec, 'base_price' => 210.00, 'image' => self::weapon('ak47.png')],
            ['weapon' => 'AK-47', 'skin' => 'Blue Laminate', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Restricted, 'base_price' => 540.00, 'image' => self::weapon('ak47.png')],

            ['weapon' => 'AWP', 'skin' => 'Dragon Lore', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 215000.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Asiimov', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 7800.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Neo-Noir', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 4900.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Atheris', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 1350.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Hyper Beast', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 3200.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Containment Breach', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 8400.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Fade', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 12700.00, 'image' => self::weapon('awp.png')],
            ['weapon' => 'AWP', 'skin' => 'Wildfire', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 9500.00, 'image' => self::weapon('awp.png')],

            ['weapon' => 'M4A4', 'skin' => 'Howl', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Contraband, 'base_price' => 189000.00, 'image' => self::weapon('m4a4.png')],
            ['weapon' => 'M4A4', 'skin' => 'Neo-Noir', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 4100.00, 'image' => self::weapon('m4a4.png')],
            ['weapon' => 'M4A4', 'skin' => 'Asiimov', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 3700.00, 'image' => self::weapon('m4a4.png')],
            ['weapon' => 'M4A4', 'skin' => 'The Emperor', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 3300.00, 'image' => self::weapon('m4a4.png')],
            ['weapon' => 'M4A4', 'skin' => 'Desolate Space', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 1450.00, 'image' => self::weapon('m4a4.png')],
            ['weapon' => 'M4A4', 'skin' => 'Cyber Security', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 2100.00, 'image' => self::weapon('m4a4.png')],

            ['weapon' => 'M4A1-S', 'skin' => 'Hot Rod', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 4200.00, 'image' => self::weapon('m4a1s.png')],
            ['weapon' => 'M4A1-S', 'skin' => 'Printstream', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 8900.00, 'image' => self::weapon('m4a1s.png')],
            ['weapon' => 'M4A1-S', 'skin' => 'Nightmare', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 1580.00, 'image' => self::weapon('m4a1s.png')],
            ['weapon' => 'M4A1-S', 'skin' => 'Decimator', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 2340.00, 'image' => self::weapon('m4a1s.png')],
            ['weapon' => 'M4A1-S', 'skin' => 'Cyrex', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 520.00, 'image' => self::weapon('m4a1s.png')],

            ['weapon' => 'AUG', 'skin' => 'Akihabara Accept', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Covert, 'base_price' => 7900.00, 'image' => self::weapon('aug.png')],
            ['weapon' => 'AUG', 'skin' => 'Bengal Tiger', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 450.00, 'image' => self::weapon('aug.png')],
            ['weapon' => 'AUG', 'skin' => 'Chameleon', 'category' => ItemCategory::Rifles, 'rarity' => ItemRarity::Classified, 'base_price' => 620.00, 'image' => self::weapon('aug.png')],

            ['weapon' => 'Desert Eagle', 'skin' => 'Blaze', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Restricted, 'base_price' => 5200.00, 'image' => self::weapon('deagle.png')],
            ['weapon' => 'Desert Eagle', 'skin' => 'Printstream', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 3300.00, 'image' => self::weapon('deagle.png')],
            ['weapon' => 'Desert Eagle', 'skin' => 'Code Red', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 1700.00, 'image' => self::weapon('deagle.png')],
            ['weapon' => 'Desert Eagle', 'skin' => 'Kumicho Dragon', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Classified, 'base_price' => 290.00, 'image' => self::weapon('deagle.png')],
            ['weapon' => 'Desert Eagle', 'skin' => 'Hypnotic', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Restricted, 'base_price' => 210.00, 'image' => self::weapon('deagle.png')],

            ['weapon' => 'Glock-18', 'skin' => 'Fade', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Restricted, 'base_price' => 4900.00, 'image' => self::weapon('glock.png')],
            ['weapon' => 'Glock-18', 'skin' => 'Water Elemental', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Classified, 'base_price' => 380.00, 'image' => self::weapon('glock.png')],
            ['weapon' => 'Glock-18', 'skin' => 'Gamma Doppler', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 2100.00, 'image' => self::weapon('glock.png')],
            ['weapon' => 'Glock-18', 'skin' => 'Neo-Noir', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 540.00, 'image' => self::weapon('glock.png')],

            ['weapon' => 'USP-S', 'skin' => 'Kill Confirmed', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 3800.00, 'image' => self::weapon('usp.png')],
            ['weapon' => 'USP-S', 'skin' => 'Neo-Noir', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 990.00, 'image' => self::weapon('usp.png')],
            ['weapon' => 'USP-S', 'skin' => 'Printstream', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 2750.00, 'image' => self::weapon('usp.png')],
            ['weapon' => 'USP-S', 'skin' => 'Orion', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Covert, 'base_price' => 1400.00, 'image' => self::weapon('usp.png')],
            ['weapon' => 'USP-S', 'skin' => 'Cortex', 'category' => ItemCategory::Pistols, 'rarity' => ItemRarity::Classified, 'base_price' => 180.00, 'image' => self::weapon('usp.png')],
        ];
    }

    /**
     * Демо-обложки кейсов — реальные файлы в public/images/playstore/goods/csgo/case/.
     *
     * @return list<array{name: string, image: string, price: float}>
     */
    public static function cases(): array
    {
        return [
            ['name' => 'Chroma Case', 'image' => self::caseImg('chroma.png'), 'price' => 249.00],
            ['name' => 'Chroma 2 Case', 'image' => self::caseImg('chroma2.png'), 'price' => 219.00],
            ['name' => 'Chroma 3 Case', 'image' => self::caseImg('chroma3.png'), 'price' => 199.00],
            ['name' => 'Hydra Case', 'image' => self::caseImg('hydra.png'), 'price' => 289.00],
            ['name' => 'Spectrum Case', 'image' => self::caseImg('spectrum.png'), 'price' => 269.00],
            ['name' => 'Spectrum 2 Case', 'image' => self::caseImg('spectrum2.png'), 'price' => 239.00],
            ['name' => 'Gamma Case', 'image' => self::caseImg('gamma.png'), 'price' => 259.00],
            ['name' => 'Gamma 2 Case', 'image' => self::caseImg('gamma2.png'), 'price' => 229.00],
            ['name' => 'Glove Case', 'image' => self::caseImg('glove.png'), 'price' => 349.00],
            ['name' => 'Falchion Case', 'image' => self::caseImg('falchion.png'), 'price' => 279.00],
            ['name' => 'Revolver Case', 'image' => self::caseImg('revolver.png'), 'price' => 189.00],
            ['name' => 'Wildfire Case', 'image' => self::caseImg('wildfire.png'), 'price' => 329.00],
            ['name' => 'Phoenix Weapon Case', 'image' => self::caseImg('phoenix.png'), 'price' => 199.00],
            ['name' => 'Operation Breakout Case', 'image' => self::caseImg('breakout.png'), 'price' => 179.00],
            ['name' => 'Operation Vanguard Case', 'image' => self::caseImg('vanguard.png'), 'price' => 219.00],
            ['name' => 'Bravo Case', 'image' => self::caseImg('bravo.png'), 'price' => 159.00],
            ['name' => 'Shadow Case', 'image' => self::caseImg('shadow.png'), 'price' => 189.00],
            ['name' => 'Huntsman Case', 'image' => self::caseImg('huntsman.png'), 'price' => 209.00],
            ['name' => 'Dragon Lore Chest', 'image' => self::caseImg('dragonlore.png'), 'price' => 1290.00],
            ['name' => 'Diamond Chest', 'image' => self::caseImg('diamond.png'), 'price' => 890.00],
            ['name' => 'Gold Chest', 'image' => self::caseImg('gold.png'), 'price' => 690.00],
            ['name' => 'Newbie Case', 'image' => self::caseImg('newbie.png'), 'price' => 49.00],
            ['name' => 'Classified Case', 'image' => self::caseImg('classified.png'), 'price' => 399.00],
            ['name' => 'Covert Case', 'image' => self::caseImg('covert.png'), 'price' => 499.00],
            ['name' => 'Restricted Case', 'image' => self::caseImg('restricted.png'), 'price' => 149.00],
            ['name' => 'Mil-Spec Case', 'image' => self::caseImg('milspec.png'), 'price' => 99.00],
            ['name' => 'Fade Chest', 'image' => self::caseImg('fade.png'), 'price' => 1490.00],
            ['name' => 'Asiimov Case', 'image' => self::caseImg('asiimov.png'), 'price' => 319.00],
        ];
    }

    private static function weapon(string $file): string
    {
        return '/images/playstore/goods/csgo/weapon/'.$file;
    }

    private static function caseImg(string $file): string
    {
        return '/images/playstore/goods/csgo/case/'.$file;
    }
}
