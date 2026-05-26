CREATE DATABASE IF NOT EXISTS videoteka_lv4
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE videoteka_lv4;

CREATE TABLE IF NOT EXISTS korisnici (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    korisnicko_ime VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    lozinka VARCHAR(255) NOT NULL,
    uloga ENUM('korisnik', 'administrator') NOT NULL DEFAULT 'korisnik',
    kreirano TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_korisnici_ime (korisnicko_ime),
    UNIQUE KEY uq_korisnici_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS filmovi (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    izvorni_id VARCHAR(20) NULL,
    naslov VARCHAR(255) NOT NULL,
    zanr VARCHAR(255) NOT NULL,
    godina SMALLINT UNSIGNED NOT NULL,
    zemlja VARCHAR(255) NULL,
    trajanje SMALLINT UNSIGNED NULL,
    prosjecna_ocjena DECIMAL(3,1) NULL DEFAULT NULL,
    opis TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_film_izvorni_id (izvorni_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibility with the earlier seed-based version of the LV4 database.
ALTER TABLE filmovi
    ADD COLUMN IF NOT EXISTS izvorni_id VARCHAR(20) NULL AFTER id,
    MODIFY COLUMN zemlja VARCHAR(255) NULL,
    MODIFY COLUMN trajanje SMALLINT UNSIGNED NULL,
    MODIFY COLUMN prosjecna_ocjena DECIMAL(3,1) NULL DEFAULT NULL;

DROP INDEX IF EXISTS uq_film_naslov_godina ON filmovi;
CREATE UNIQUE INDEX IF NOT EXISTS uq_film_izvorni_id ON filmovi (izvorni_id);

CREATE TABLE IF NOT EXISTS zeljeni_filmovi (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    korisnik_id INT UNSIGNED NOT NULL,
    film_id INT UNSIGNED NOT NULL,
    dodano TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_korisnik_film (korisnik_id, film_id),
    CONSTRAINT fk_zeljeni_korisnik
        FOREIGN KEY (korisnik_id) REFERENCES korisnici (id) ON DELETE CASCADE,
    CONSTRAINT fk_zeljeni_film
        FOREIGN KEY (film_id) REFERENCES filmovi (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS slike (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naziv VARCHAR(120) NOT NULL,
    putanja VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slike_putanja (putanja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ocjene_slika (
    korisnik_id INT UNSIGNED NOT NULL,
    slika_id INT UNSIGNED NOT NULL,
    ocjena TINYINT UNSIGNED NOT NULL,
    vrijeme_ocjene TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (korisnik_id, slika_id),
    CONSTRAINT fk_ocjena_korisnik
        FOREIGN KEY (korisnik_id) REFERENCES korisnici (id) ON DELETE CASCADE,
    CONSTRAINT fk_ocjena_slika
        FOREIGN KEY (slika_id) REFERENCES slike (id) ON DELETE CASCADE,
    CONSTRAINT chk_ocjena_slika CHECK (ocjena BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO korisnici (korisnicko_ime, email, lozinka, uloga)
VALUES ('administrator', 'admin@videoteka.local', '$2y$10$kbUkn4ubIL9Nq5yP1E5X3eLRMxaHMDHrgki32X8r/qXBZ/511VTa6', 'administrator')
ON DUPLICATE KEY UPDATE korisnicko_ime = korisnicko_ime;

-- Films are imported from public/data/netflix_titles.csv through import_films.php.

INSERT INTO slike (naziv, putanja) VALUES
('Pustinja', 'public/images/desert.jpg'),
('Kukac', 'public/images/insect.jpg'),
('Lav', 'public/images/lion.jpg'),
('Planine', 'public/images/mountians.jpg'),
('Kamenje', 'public/images/stones.jpg'),
('Tigar', 'public/images/tiger.jpg')
ON DUPLICATE KEY UPDATE naziv = VALUES(naziv);
