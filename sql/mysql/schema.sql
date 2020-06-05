START TRANSACTION;

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `path` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'application/octet-stream',
  `gallery_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `galleries` (
  `id` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gallery_items` (
  `gallery_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gallery_thumbnails` (
  `gallery_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_files_galleries` (`gallery_id`);

ALTER TABLE `galleries`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gallery_items`
  ADD PRIMARY KEY (`gallery_id`,`file_id`,`position`),
  ADD KEY `fk_gallery_items_files` (`file_id`);

ALTER TABLE `gallery_thumbnails`
  ADD PRIMARY KEY (`gallery_id`,`file_id`,`position`),
  ADD KEY `fk_gallery_thumbs_files` (`file_id`);

ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `galleries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `files`
  ADD CONSTRAINT `fk_files_galleries` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON UPDATE CASCADE;

ALTER TABLE `gallery_items`
  ADD CONSTRAINT `fk_gallery_items_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gallery_items_galleries` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `gallery_thumbnails`
  ADD CONSTRAINT `fk_gallery_thumbs_files` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gallery_thumbs_galleries` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
