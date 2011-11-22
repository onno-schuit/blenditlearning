<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Automatically generated strings for Moodle 2.1 installer
 *
 * Do not edit this file manually! It contains just a subset of strings
 * needed during the very first steps of installation. This file was
 * generated automatically by export-installer.php (which is part of AMOS
 * {@link http://docs.moodle.org/dev/Languages/AMOS}) using the
 * list of strings defined in /install/stringnames.txt.
 *
 * @package   installer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['admindirname'] = 'Dossier d\'administration';
$string['dataroot'] = 'Dossier de données';
$string['dbprefix'] = 'Préfixe des tables';
$string['dirroot'] = 'Dossier Moodle';
$string['installation'] = 'Installation';
$string['memorylimithelp'] = '<p>La limite de mémoire de PHP sur votre serveur est actuellement de {$a}.</p> <p>Cette valeur très faible risque de générer des problèmes de manque de mémoire pour Moodle, notamment si vous utilisez beaucoup de modules et/ou si vous avez un grand nombre d\'utilisateurs.</p> <p>Il est recommandé de configurer PHP avec une limite de mémoire aussi élevée que possible, par exemple 16 Mo. Vous pouvez obtenir cela de différentes façons :
<ol>
<li>si vous en avez la possibilité, recompilez PHP avec l\'option <i>--enable-memory-limit</i>. Cela permettra à Moodle de fixer lui-même sa limite de mémoire ;</li>
<li>si vous avez accès à votre fichier « php.ini », vous pouvez attribuer au paramètre <b>memory_limit</b> une valeur comme 40M. Si vous n\'y avez pas accès, demandez à l\'administrateur de le faire pour vous ;</li>
<li>sur certains serveur, vous pouvez créer dans le dossier principal de Moodle un fichier « .htaccess » contenant cette ligne : <p><blockquote>php_value memory_limit 40M</blockquote></p> <p>Cependant, sur certains serveur, cela empêchera le fonctionnement correcte de <b>tous</b> les fichiers PHP (vous verrez s\'afficher des erreurs lors de la consultation de pages). Dans ce cas, vous devrez supprimer le fichier « .htaccess ».</li>
</ol>';
$string['phpversion'] = 'Version de PHP';
$string['phpversionhelp'] = '<p>Moodle nécessite au minimum la version 4.1.0 de PHP.</p> <p>Vous utilisez actuellement la version {$a}.</p> <p>Pour que Moodle fonctionne, vous devez mettre à jour PHP ou aller chez un hébergeur ayant une version récente de PHP.</p>';
$string['wwwroot'] = 'Adresse web';
