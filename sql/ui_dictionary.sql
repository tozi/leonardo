-- UI Dictionary (UI prvky, tlačidlá, labels – preložiteľné do všetkých jazykov)
-- Táto tabuľka sa vykonáva v schema.sql

CREATE TABLE IF NOT EXISTS ui_dictionary (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dict_key VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) DEFAULT 'general',
    default_text VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- UI Dictionary Translations (preklady do jednotlivých jazykov)
CREATE TABLE IF NOT EXISTS ui_dictionary_translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dict_id INT UNSIGNED NOT NULL,
    lang_code VARCHAR(5) NOT NULL,
    translated_text VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_dict_lang (dict_id, lang_code),
    FOREIGN KEY (dict_id) REFERENCES ui_dictionary(id) ON DELETE CASCADE,
    FOREIGN KEY (lang_code) REFERENCES languages(code) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Predvolené UI prvky v Slovenčine
INSERT INTO ui_dictionary (dict_key, category, default_text, description, sort_order) VALUES
-- TLAČIDLÁ
('btn_back', 'buttons', 'Späť', 'Tlačidlo späť', 10),
('btn_back_to_blog', 'buttons', '← Späť na blog', 'Tlačidlo späť na blog', 11),
('btn_save', 'buttons', 'Uložiť', 'Tlačidlo uložiť', 12),
('btn_delete', 'buttons', 'Zmazať', 'Tlačidlo zmazať', 13),
('btn_edit', 'buttons', 'Upraviť', 'Tlačidlo upraviť', 14),
('btn_cancel', 'buttons', 'Zrušiť', 'Tlačidlo zrušiť', 15),
('btn_submit', 'buttons', 'Odoslať', 'Tlačidlo odoslať', 16),
('btn_search', 'buttons', 'Hľadať', 'Tlačidlo hľadať', 17),
('btn_close', 'buttons', 'Zatvoriť', 'Tlačidlo zatvoriť', 18),
('btn_read_more', 'buttons', 'Čítať viac →', 'Tlačidlo čítať viac', 19),
('btn_view_more', 'buttons', 'Zobraziť viac', 'Tlačidlo zobraziť viac', 20),
('btn_inquiry', 'buttons', 'Nezáväzný dopyt', 'Tlačidlo dotazy', 21),
('btn_contact', 'buttons', 'Kontaktovať', 'Tlačidlo kontakt', 22),
('btn_download', 'buttons', 'Stiahnuť', 'Tlačidlo stiahnuť', 23),
('btn_upload', 'buttons', 'Nahrať', 'Tlačidlo nahrať', 24),
('btn_add', 'buttons', 'Pridať', 'Tlačidlo pridať', 25),
('btn_next', 'buttons', 'Ďalej →', 'Tlačidlo ďalej', 26),
('btn_prev', 'buttons', '← Predchádzajúce', 'Tlačidlo predchádzajúce', 27),

-- LABELY
('lbl_search', 'labels', 'Hľadať...', 'Placeholder hľadania', 30),
('lbl_category', 'labels', 'Kategória', 'Kategória', 31),
('lbl_categories', 'labels', 'Kategórie', 'Plurál kategória', 32),
('lbl_date', 'labels', 'Dátum', 'Dátum', 33),
('lbl_author', 'labels', 'Autor', 'Autor', 34),
('lbl_published', 'labels', 'Publikované', 'Publikované', 35),
('lbl_updated', 'labels', 'Aktualizované', 'Aktualizované', 36),
('lbl_share', 'labels', 'Zdieľať:', 'Zdieľať', 37),
('lbl_language', 'labels', 'Jazyk', 'Jazyk', 38),
('lbl_menu', 'labels', 'Menu', 'Menu', 39),
('lbl_home', 'labels', 'Domov', 'Domov', 40),
('lbl_blog', 'labels', 'Blog', 'Blog', 41),
('lbl_gallery', 'labels', 'Galéria', 'Galéria', 42),
('lbl_contact', 'labels', 'Kontakt', 'Kontakt', 43),
('lbl_about', 'labels', 'O nás', 'O nás', 44),
('lbl_services', 'labels', 'Služby', 'Služby', 45),
('lbl_no_results', 'labels', 'Žiadne výsledky', 'Žiadne výsledky', 46),
('lbl_loading', 'labels', 'Načítavanie...', 'Načítavanie', 47),
('lbl_error', 'labels', 'Chyba', 'Chyba', 48),
('lbl_success', 'labels', 'Úspešne', 'Úspešne', 49),

-- FORMULÁRE
('form_name', 'forms', 'Meno', 'Pole meno', 50),
('form_email', 'forms', 'E-mail', 'Pole email', 51),
('form_phone', 'forms', 'Telefón', 'Pole telefón', 52),
('form_message', 'forms', 'Správa', 'Pole správa', 53),
('form_subject', 'forms', 'Predmet', 'Pole predmet', 54),
('form_required', 'forms', 'Povinné pole', 'Chyba povinného poľa', 55),

-- SOCIÁLNE SIETE
('social_facebook', 'social', 'Facebook', 'Facebook', 60),
('social_instagram', 'social', 'Instagram', 'Instagram', 61),
('social_twitter', 'social', 'Twitter / X', 'Twitter / X', 62),
('social_linkedin', 'social', 'LinkedIn', 'LinkedIn', 63),
('social_youtube', 'social', 'YouTube', 'YouTube', 64),

-- SPRÁVY
('msg_copied', 'messages', 'Odkaz bol skopírovaný. Vložte ho do Instagramu (príbeh, správa alebo bio).', 'Správa skopírovaný odkaz', 70),
('msg_copy_error', 'messages', 'Odkaz sa nepodarilo skopírovať: ', 'Chyba kopírovania', 71),
('msg_confirm_delete', 'messages', 'Ste si istí, že chcete zmazať?', 'Potvrdenie zmazania', 72);
