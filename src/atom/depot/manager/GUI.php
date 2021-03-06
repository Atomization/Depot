<?php

namespace atom\depot\manager;


use atom\depot\Main;
use atom\depot\Store;
use atom\gui\type\CustomGui;
use atom\gui\type\SimpleGui;
use atom\gui\GUI as Form;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class GUI {

    /** @var Config */
    private static $store;

    /** array */
    private static $identifier = [];

    /**
     * @param string $name
     */
    public static function register(string $name): void {

        self::$store = new Config(Main::getInstance()->getDataFolder()."store.yml", Config::YAML);
        $gui = new SimpleGui();
        $gui->setTitle("Merchant!");

        foreach (self::$store->getAll() as $category => $item){
            $gui->addButton($category);
        }

        /** $data = category button text */
        $gui->setAction(function (Player $player, string $data) {
            self::send($player, $data);
        });

        Form::register($name, $gui);
        self::registerProducts();
        self::registerTransaction();
    }

    /**
     * @param Player $player
     * @param string $name
     */
    public static function send(Player $player, string $name): void {
        Form::send($player, $name);
    }

    private static function registerProducts(): void {
        foreach(self::$store->getAll() as $category => $products) {
            self::$identifier[$category] = [];
            foreach($products as $product) {
                $productMeta = explode("-", $product);
                array_push(self::$identifier[$category], $productMeta);
            }
        }

        foreach(self::$identifier as $category => $productMeta) {
            $productUi = new SimpleGui();
            $productUi->setTitle($category);
            foreach($productMeta as $product) {
                /** 2: index of item name to set as button. */
                $itemId = explode(":", $product[0]);
                $id = $itemId[0];
                $meta = 0;
                if (isset($itemId[1])) {
                    $meta = $itemId[1];
                } else {
                    $meta = 0;
                }
                $icon = "http://shop-icons.herokuapp.com/depot/icons/$id-$meta.png";
                $productUi->addButton($product[2], $icon);
            }
            $productUi->addButton(TextFormat::DARK_RED.TextFormat::BOLD."✕ 'RETURN'");
            $productUi->setAction(function (Player $player, $data){
                if ($data == TextFormat::DARK_RED.TextFormat::BOLD."✕ 'RETURN'"){
                    self::send($player, "shop-gui");
                } else {
                    self::send($player, $data);
                }
            });
            Form::register($category, $productUi);
        }

    }

    private static function registerTransaction(): void {
        foreach(self::$identifier as $category => $productMeta) {
            foreach($productMeta as $product) {
                /**
                 * 0: id:meta
                 * 1: price
                 * 2: name
                 */
                $itemId = explode(":", $product[0]);
                $price = $product[1];
                $name = $product[2];
                $transactionUI = new CustomGui();
                $transactionUI->setTitle("Buying/Selling: " . $name);
                // $transactionUI->addLabel(TextFormat::YELLOW."My Balance: $" . Store::getMoney($player));
                $transactionUI->addLabel(TextFormat::GREEN."Buy for: $" . $price);
                $transactionUI->addLabel(TextFormat::RED."Sell for: $" . (25/100) * $price);
                $transactionUI->addToggle("Buy/Sell");
                $transactionUI->addSlider("Amount", 1, 64);
                $transactionUI->setAction(function (Player $player, $data) use ($price, $itemId, $name) {
                    /**
                     * 2: bool: true=buying false=selling
                     * 3: amount
                     */
                    if (!$data[2]){
                        if (isset($itemId[1])){
                            $item = ItemFactory::get($itemId[0], $itemId[1], $data[3]);
                            $item->setCustomName($name);
                        } else {
                            $item = ItemFactory::get($itemId[0], 0, $data[3]);
                            $item->setCustomName($name);
                        }
                        $bulk_price = $price * $data[3];
                        Store::buy($player, $item, $bulk_price);
                    } else {
                        if (isset($itemId[1])) {
                            $item = ItemFactory::get($itemId[0], $itemId[1], $data[3]);
                        } else {
                            $item = ItemFactory::get($itemId[0], 0, $data[3]);
                        }
                        $bulk_price = $price * $data[3];
                        Store::sell($player, $item, (25/100)*$bulk_price);
                    }
                });
                Form::register($name, $transactionUI);
            }
        }
    }
}
