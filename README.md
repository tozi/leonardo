# Leonardowin CMS

PHP + MySQL CMS (Bootstrap 5 + Quill).

## Funkcie

- Podstránky, galérie, šablóny (home, page, gallery, contact, fullwidth, sidebar, blog)
- **Quill** editor (obrázky sa nahrávajú na server, tlačidlo `</> HTML` na úpravu zdrojového kódu)
- Menu s **drag & drop**
- **Blog** (články)
- **Cookie lišta**
- **Viacjazyčnosť**: správa jazykov (`/admin/languages.php`), preklady stránok, článkov a položiek menu v záložkách priamo v editore; jazykový prepínač na webe
- **Zdieľanie** stránok a článkov na Facebook a Instagram
- **Open Graph** + Twitter karty, canonical, hreflang
- **Export stránky do PDF** (`/pdf/slug`)
- **AI index**: `/ai.json`, `/llms.txt`, JSON-LD, sitemap, robots.txt
- Zmena hesla, médiá, nastavenia

## AI pre vyhľadávače a LLM

| URL | Popis |
|-----|--------|
| `/ai.json` | Kompletný JSON index (stránky, blog, menu, kontakt) |
| `/llms.txt` | Textový popis webu pre AI crawlers |
| `/sitemap.xml` | XML sitemap |
| JSON-LD | Schema.org v každej stránke (Organization, WebSite, WebPage/BlogPosting) |

## Inštalácia

1. Rozbaľte ZIP na server  
2. Importujte **celý** `sql/schema.sql`  
3. Upravte `includes/config.php`  
4. `chmod 755 assets/uploads`  
5. Admin: `/admin/` → **admin / admin123** → zmeňte heslo  

## Quill

Editor sa načítava z CDN (jsDelivr, `quill@2.0.3`) – žiadny API kľúč netreba.
Ak v editore obsah nezmeníte, pri uložení sa odošle pôvodné HTML (Quill nepozná napr. tabuľky, tak sa nestratia).
Ak obsah upravíte, uloží sa HTML, ktoré Quill podporuje (nadpisy, zoznamy, odkazy, obrázky, citácie, zarovnanie…).
Pre tabuľky použite tlačidlo `</> HTML`.

## Zdieľanie na sociálne siete

- **Facebook** – oficiálny dialóg `sharer.php`.
- **Instagram** – Instagram nemá web odkaz na zdieľanie URL. Na mobile sa otvorí systémový zdieľací panel, na desktope sa odkaz skopíruje a otvorí sa Instagram.
- Tlačidlá sú v `templates/share.php` (vložené do šablón page, fullwidth, sidebar, gallery, post). Do ďalšej šablóny ich pridáte riadkom `<?php include __DIR__ . '/share.php'; ?>`.

## Open Graph

Generuje sa v `templates/header.php`: `og:title`, `og:description`, `og:url`, `og:type`, `og:locale`, `og:image` (hlavný obrázok → prvý obrázok v obsahu → predvolený obrázok v Nastaveniach), Twitter karty a `canonical`.
**Dôležité:** v `includes/config.php` nastavte `SITE_URL` na verejnú doménu (nie `localhost`), inak Facebook obrázok ani stránku nenačíta.
Ladenie: https://developers.facebook.com/tools/debug/

## Preklady

1. `/admin/languages.php` – pridajte jazyk (základný jazyk je Slovenčina).
2. V editore stránky / článku je karta **Preklady** so záložkou pre každý jazyk (názov, obsah, perex, meta údaje). Názvy položiek menu sa prekladajú v správe menu.
3. Pole, ktoré v preklade nevyplníte, sa zobrazí v základnom jazyku.
4. Na webe sa jazyk prepína cez `?lang=xx` (uloží sa do cookie). Odkazy na zdieľanie obsahujú `?lang=xx`, takže sa otvoria v správnom jazyku.
