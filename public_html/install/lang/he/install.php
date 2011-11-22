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

$string['admindirname'] = 'ספריית מנהל המערכת';
$string['availablelangs'] = 'חבילות שפה זמינות';
$string['chooselanguagehead'] = 'בחר שפה';
$string['chooselanguagesub'] = 'אנא בחר שפה עבור ההתקנה בלבד. תוכל לבחור בשפה שונה לאתר ולמשתמש באחד מהמסכים הבאים.';
$string['clialreadyinstalled'] = 'קובץ ה-config.php קיים כבר, אנא השתמש ב- admin/cli/upgrade.php
אם ברצונך לשדרג את האתר שלך.';
$string['cliinstallheader'] = 'תוכנית התקנת Moodle {$a} בשורת הפקודה';
$string['databasehost'] = 'מסד הנתונים המארח (host)';
$string['databasename'] = 'שם מסד הנתונים';
$string['databasetypehead'] = 'בחר התקן מסד הנתונים';
$string['dataroot'] = 'ספריית הנתונים';
$string['dbprefix'] = 'Tables prefix';
$string['dirroot'] = 'ספריית ה-Moodle';
$string['environmenthead'] = 'בודק את הסביבה שלך...';
$string['environmentsub2'] = 'לכל התקנת Moodle יש דרישות מינימליות לגרסת ה-PHP ומספר הרחבות הכרחי של ה-PHP.
בדיקת הסביבה הושלמה לפני התקנת כל אחד ושדרוגו.אם הינך מתקשה, אנא פנה למנהל המערכת בכדי להתקין גרסת PHP חדשה או לאפשר הרחבות PHP.';
$string['errorsinenvironment'] = 'בדיקת הסביבה נכשלה!';
$string['installation'] = 'התקנה';
$string['langdownloaderror'] = 'לצערינו השפה "{$a}" לא הותקנה. תהליך ההתקנה ימשיך באנגלית.';
$string['memorylimithelp'] = '<p>
גבול הזיכרון של ה-PHP לשרת שלך כרגע מכוון ל-{$a}
</p>
<p>
דבר זה עלול לגרום בעיות זיכרון בהמשך, במיוחד אם יש לך מודולים רבים פעילים אוו הרבה משתמשים. </p>
<p> אנו ממליצים שתעצב את הגדרת ה-PHP עם ערך גבוה להגבלת הזיכרון, כמו 40M.
ישנן דרכים רבות לכך:
<ol>
<il>
אם תוכל להדר את PHP שוב עם <i> -- enable-memory-limit </i>
דבר זה יאפשר ל-Moodle להגדיר את גבול הזיכרון לבד. </i>
<i> אם יש לך גישה לקובץ ה-php.ini, תוכל לשנות את משתנה ה- <b> memory_limit </b>
שנה שם את הערך למשל ל-40M. אם אין לך גישה לקובץ זה תוכל לבקש ממנהל המערכת שלך שיעשה זאת עבורך.
</i>
<i> בכמה שרתי PHP תוכל ליצור קובץ  .htaccess בספריית  ה-Moodle שלך בצירוף שורה זו:
<p><blockquote>php_value memory_limit 40M</blockquote></p>
<p> בכל אופן, בכמה שרתים דבר זה ימנע <b>מכל </b> הדפים לעבוד ( אם תראה שגיאות כאשר תיכנס לדפים) תדע כי הינך צריך להסיר את הקובץ  .htaccess.
</p>
</il>
</ol>

</p>';
$string['paths'] = 'נתיבים';
$string['pathserrcreatedataroot'] = 'ספריית המידע (Data Directory) - ({$a->dataroot}) לא יכולה להיווצר על-ידי המתקין.';
$string['pathshead'] = 'נתיבים מאושרים';
$string['pathsrodataroot'] = 'ספריית המידע (Data Directory) לא ניתנת לכתיבה.';
$string['pathsroparentdataroot'] = 'ספריית האב - ({$a->parent}) לא ניתנת לכתיבה. 
ספריית המידע (Data Directory) - ({$a->dataroot}) לא יכולה להיווצר על-ידי המתקין. ';
$string['pathssubdirroot'] = 'הנתיב המלא לספריית ההתקנה של Moodle';
$string['pathsunsecuredataroot'] = 'ספריית המידע (Data Directory) לא מאובטחת';
$string['pathswrongadmindir'] = 'ספריית ה-admin לא קיימת';
$string['phpextension'] = 'הרחבת PHP {$a}';
$string['phpversion'] = 'גירסת PHP';
$string['phpversionhelp'] = '<p>גירסת PHP חייבת להיות לפחות 4.3.0 או 5.1.0 (בגירסאות 5.0.x קיימות מספר בעיות ידועות) </p>
<p> במערכת שלך פועלת כרגע גירסת {$a} </p>
<p> אתה חייב לשדרג את גירסת ה-PHP שלך או לעבור למחשב מארח עם עם גירסת PHP חדשה! <br/>
(במקרים של גרסת 5.0.x תוכל גם לרדת בגירסה ל- 4.4.x)
</p>';
$string['welcomep10'] = '{$a->installername} ({$a->installerversion})';
$string['welcomep20'] = 'הינך רואה את עמוד זה מפני שהתקנת והפעלת בהלכה את <strong> $a-packname {$a->packversion} 
</strong>
חבילה במחשבך. ברכותינו!';
$string['welcomep30'] = 'גירסת <strong>{$a->installername}</strong> כוללת את היישומים ליצור סביבה אשר בה <strong> Moodle </strong>
יפעל דהיינו:';
$string['welcomep40'] = 'החבילה כוללת בנוסף 
<strong>Moodle {$a->moodlerelease} ({$a->moodleversion})</strong>.';
$string['welcomep50'] = 'השימוש בכל היישומים בחבילה זו מפוקח ע"י הרשיונות המתאימים להם. החבילה 
<strong>{$a->installername}</strong>
השלמה היא 
<a href="http://www.opensource.org/docs/definition_plain.html"> קוד פתוח
</a>
והיא מבוזרת תחת רישיון
<a>
href="http://www.gnu.org/copyleft/gpl.html">GPL</a>';
$string['welcomep60'] = 'העמודים הבאים יובילו אותך בצורה פשוטה דרך כמה צעדים לעיצוב הגדרות <strong>Moodle</strong> במחשבך.
תוכל לאשר את הגדרות  ברירת המחדל או, באפשרותך, לשנותם לפי צרכיך.';
$string['welcomep70'] = 'הקש על לחצן ה"המשך" למטה כדי להמשיך עם הגדרת ה-<strong>Moodle</strong>';
$string['wwwroot'] = 'כתובת האתר';
