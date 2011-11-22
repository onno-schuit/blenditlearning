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

$string['admindirname'] = 'Admin դիրեկտորիա';
$string['availablelangs'] = 'Մատչելի լեզվային փաթեթներ';
$string['chooselanguagehead'] = 'Ընտրեք լեզուն';
$string['chooselanguagesub'] = 'Ընտրեք լեզուն ՄԻԱՅՆ տեղակայման համար: Հետագա էկրաններից դուք հնարավորություն կունենաք ընտրել կայքի և օգտագործողի լեզուն:';
$string['dataroot'] = 'Տվյալների դիրեկտորիա';
$string['dbprefix'] = 'Աղյուսակների նախածանց';
$string['dirroot'] = 'Moodle դիրեկտորիա';
$string['environmenthead'] = 'Միջավայրի ստուգում  ...';
$string['installation'] = 'Տեղակայում';
$string['langdownloaderror'] = 'Ցավոք "{$a}" լեզուն տեղակայված չէ և տեղակայման գործընթացը կշարունակվի անգլերենով։';
$string['memorylimithelp'] = '<p>PHP-ի հիշողության սահմանը սպասարկչի համար ներկայումս սահմանված է՝ {$a}։</p>

<p>հետագայում կարող եք հիշողության հետ կապված խնդիրներ ունենալ, 
   եթե Moodle-ում ունենաք շատ մոդուլներ և/կամ մեծ թվով օգտագործողներ։</p>

<p>Խորհուրդ ենք տալիս PHP-ն կազմաձևել հնարավորին շատ հիշողության համար, օրինակ` 40M: Դրա համար կան մի քանի ձևեր, որոնք կարող եք փորձել.</p>
<ol>
<li>Եթե դուք կարող եք, վերակազմարկել PHP-ն <i>--enable-memory-limit</i>-ով։ Այն թույլ կտա Moodle-ին ինքնուրույն կարգաբերել հիշողության սահմանը։</li>
<li>Եթե Ձեզ մատչելի է php.ini ֆայլը, կարող եք փոխել <b>memory_limit</b>-ը՝ 
    կարգաբերելով մոտավորապես 40M։  </li>
<li>Որոշ PHP սպասարկիչներում Moodle դիրեկտորիայում կարող եք ստեղծել .htaccess ֆայլը, որը պարունակում է այս տողը՝
    <blockquote><div>php_value memory_limit 40M</div></blockquote>
    <p>Սակայն որոշ սպասարկիչներում սա կկանխարգելի <b>բոլոր</b> PHP էջերի աշխատելը 
    (դուք կտեսնեք սխալներ էջերը դիտելիս), այսպիսով դուք պետք է ջնջեք .htaccess ֆայլը։</p></li>
</ol>';
$string['phpversion'] = 'PHP տարբերակ';
$string['phpversionhelp'] = '<p>Moodle Մուդլը պահանջում է PHP 4.3.0 կամ 5.1.0 տարբերակները (5.0.x -ը ունի որոշ հայտնի խնդիրներ)։</p>
<p>Դուք գործարկում եք {$a} տարբերակը</p>
<p>Անհրաժեշտ է բարելավել PHP-ն կամ տեղափոխել նոր PHP տարբերակով սպասարկչի վրա։<br />
(5.0.x-ի դեպքում կարող եք իջեցնել մինչև 4.4.x տարբերակի)</p>';
$string['welcomep10'] = '{$a->installername} ({$a->installerversion})';
$string['welcomep20'] = 'Դուք տեսնում եք այս էջը, քանի որ հաջողությամբ տեղակայել և գործարկել <strong>{$a->packname} {$a->packversion}</strong> փաթեթը։ Շնորհավորանքներ։';
$string['welcomep30'] = '<strong>{$a->installername}</strong> թողարկումը պարունակում է  կիրառական ծրագրեր, որոնք ստեղծում են միջավայր, որտեղ <strong>Moodle</strong> կաշխատի, մասնավորապես՝';
$string['welcomep40'] = 'Այս փաթեթը պարունակում է նաև <strong>Moodle {$a->moodlerelease} ({$a->moodleversion})</strong>։';
$string['welcomep50'] = 'Փաթեթի բոլոր կիրառական ծրագրերի օգտագործումը ղեկավարվում է դրանց համապատասխան արտոնագրերով: Ամբողջական <strong>{$a->installername}</strong> փաթեթը 
    <a href="http://www.opensource.org/docs/definition_plain.html">բաց կոդով է</a> և տրամադրվում է <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a> արտոնագրով։';
$string['welcomep60'] = 'Հետևյալ էջերի որոշ հեշտ քայլերին հետևելով՝ կարող եք <strong>Moodle</strong> տեղակայել Ձեր համակարգչում։ Դուք կարող եք համաձայնվել լռելյայն կարգաբերումների հետ կամ փոխել դրանք՝ ձեր պահանջներին համապատասխան:';
$string['welcomep70'] = 'Սեղմեք ստորև գտնվող \'Հաջորդ\' կոճակը, որպեսզի անցնեք <strong>Moodle</strong>-ի կարգաբերման հաջորդ քայլին:';
$string['wwwroot'] = 'Ցանցային հասցե';
