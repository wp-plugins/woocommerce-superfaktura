=== WooCommerce SuperFaktura ===
Contributors: webikon, johnnypea
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ZQDNE7TP3XT36
Tags: superfaktura, invoice, faktura, proforma, woocommerce
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 1.4.12
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your WooCommerce eShop with online invoicing system SuperFaktura.

== Description ==

SuperFaktura extension for WooCommerce enables you to create invoices using third-party online app SuperFaktura.  

SuperFaktura is an online invoicing system for small business owners available in Slovakia ([superfaktura.sk](http://www.superfaktura.sk/)) and Czech Republic ([superfaktura.cz](http://www.superfaktura.cz/)).

Main features of WooCommerce Superfaktura include:

* Automatically create invoices in SuperFaktura.
* Add fields for invoice details to WooCommerce Checkout form.
* Link to the invoice is added to 
	* Customer notification email sent by WooCommerce
	* Order detail
	* WooCommerce My Account page
* Set your own rules, when proforma or real invoice should be generated. Want to send proforma invoice on order creation and real invoice after payment? We got that covered.
* Custom invoice numbering. 

This plugin is not directly associated with superfaktura.sk, s.r.o. or with superfaktura cz, s.r.o. or oficially supported by their developers.

Created by [Ján Bočínec](http://bocinec.sk/) with the support of [Slovak WordPress community](http://wp.sk/) and [WordPress agency Webikon](http://www.webikon.sk/). 

For priority support and more Woocommerce extensions (payment gateways, invoicing…) check [PlatobneBrany.sk](http://platobnebrany.sk/)

== Installation ==

1. Upload the entire SuperFaktura folder *woocommerce-superfaktura* to the /wp-content/plugins/ directory (or use WordPress native installer in Plugins -> Add New Plugin). And activate the plugin through the 'Plugins' menu in WordPress.
2. Visit your SuperFaktura account and get an API key
3. Set your SuperFaktura Account Email and API key in *WooCommerce -> Settings -> SuperFaktura*

== Screenshots ==
Coming soon.

== Frequently Asked Questions ==

= Invoice is not created automatically =

Check the settings in *WooCommerce -> Settings -> SuperFaktura*
You should fill your Account Email, API key and set the Order status in which you would like to create the invoice.

= Invoice is marked as paid =

Status of the payment is related to Order status. When an invoice is created with the status “On-Hold”, it will not be marked as paid. When an invoice is created with the status “Completed”, it will be marked as paid.

= The plugin stopped working and I don’t know why! =

This usually happens when you change your login email address. The email address in *WooCommerce -> Settings -> SuperFaktura* must be the same as the one you use to log in to SuperFaktura.

= Where can I find more information about SuperFaktura API? =

You can read more about SuperFaktura API integration at [superfaktura.sk/api](http://www.superfaktura.sk/api/)

== Changelog ==

= 1.4.12 =
* Fixed item subtotal rounding.

= 1.4.11 =
* Upravené posielanie fakturačnej a dodacej adresy

= 1.4.10 =
* Opravená zľava pri produkte vo výpredaji

= 1.4.9 =
* Opravené aplikácia kupónov
* Opravené zamenené zadanie telefónom a emailom
* Pridaná možnosť zobrazovať popisky pod jednotlivými položkami faktúry

= 1.4.7 =
* Opravené aplikovanie zľav pri zadaní konkrétnej sumy
* Pridané zarátavanie poplatkov

= 1.4.6 =
* Opravené vystavovanie faktúr pri variáciách produktov

= 1.4.5 =
* Pridaná možnosť nastaviť, pri ktorých spôsoboch dodania sa na faktúre zobrazuje dátum dodania
* Opravené vytváranie faktúr pre českú verziu SuperFaktura.cz
* Opravené prehodené telefónne číslo a email klienta
* Opravené správne vypočítavanie zľavových kupónov (momentálne nie je možné miešať percentuálne zľavy a zľavy na konkrétnu sumu, SuperFaktúra vždy upredností percentá)

= 1.4.0 =
* Vo faktúre sa zobrazujú zľavnené produkty
* Opravená zľava pri aplikovaní kupónu
* Pridaná možnosť vlastných komentárov
* Štát sa teraz klientom priraďuje správne

= 1.3.0 =
* Pridaný oznam o daňovej povinnosti
* Zobrazuje sa celý názov štátu
* Predĺžená doba získavania PDF faktúry z API servera SuperFaktúry, aby neostala táto hodnota prázdna

= 1.2.3 =
* Opravené zobrazovanie štátu odberateľa na faktúre 

= 1.2.2 =
* Opravený problém zmiznutých nastavení

= 1.2.1 =
* Opravené generovanie faktúr

= 1.2 =
* Kompatibilita s Woocommerce 2.2
* Pridaná možnosť vybrať si slovenskú alebo českú verziu

= 1.1.6 =
* Opravené delenie nulou pri poštovnom zadarmo

= 1.1.5 =
* Opravené prekladanie pomocou po/mo súborov
* Pridané slovenské jazykové súbory
* Automatické pridávanie čísla objednávky do poznámky

= 1.1.4 =
* Opravená kompatibilita s WooCommerce 2.1

= 1.1.3 =
* V zozname modulov pribudla moznost Settings
* Opravena chyba, ktora sa vyskytovala pri zmene stavu objednavky
* Pridane zobrazovanie postovneho na fakture
* Pridane cislo objednavky vo fakture
* Zmeneny vypocet dane

= 1.1.2 =
* Opravené nezobrazovanie názvu firmy vo faktúre

= 1.1.1 =
* Opravený bug v dani.
* Pridané posielane faktúry zákazníkovi mailom (odkaz na stiahnutie faktúry)

= 1.1.0 =
* Pridaný link na faktúru do emailu.

= 1.0.0 =
Prvotné vydanie.
