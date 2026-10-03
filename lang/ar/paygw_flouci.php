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
 * Strings for component 'paygw_flouci', language 'ar'.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['amount'] = 'المبلغ';
$string['apierror'] = 'تعذّر الاتصال بخدمة الدفع (Flouci) أو أنها أعادت ردًّا غير متوقع. يرجى المحاولة مرة أخرى بعد بضع دقائق.';
$string['cannotstart'] = 'تعذّر بدء عملية الدفع. لم يتم خصم أي مبلغ من حسابك. يرجى المحاولة مرة أخرى أو الاتصال بمسؤول الموقع.';
$string['checkagain'] = 'التحقق مرة أخرى';
$string['deliverypending'] = 'تم استلام دفعتك. جارٍ إنهاء عملية الشراء وستكون متاحة بعد لحظات.';
$string['event_payment_completed'] = 'اكتمال دفعة Flouci';
$string['event_payment_mismatch'] = 'دفعة Flouci غير مطابقة';
$string['flouciid'] = 'معرّف دفعة Flouci';
$string['gatewaydescription'] = 'تتيح لك Flouci الدفع بالدينار التونسي عبر محفظة Flouci أو بطاقة مصرفية.';
$string['gatewayname'] = 'Flouci';
$string['id'] = 'المعرّف';
$string['invalidamount'] = 'المبلغ المطلوب دفعه غير صالح.';
$string['invalidkey'] = 'يجب ألا يحتوي المفتاح على مسافات أو نقطتين.';
$string['item'] = 'العنصر (المكوّن / المنطقة / المعرّف)';
$string['lasterror'] = 'آخر رسالة';
$string['lockfailed'] = 'تتم معالجة الدفعة حاليًا بواسطة طلب آخر. يرجى المحاولة بعد لحظات.';
$string['mismatchesfound'] = 'تم الإبلاغ عن {$a} دفعة/دفعات Flouci لأن المبلغ أو المرجع الذي أبلغت عنه Flouci لا يطابق المطلوب. لم يتم تسليم الطلب. يرجى مراجعتها وردّ المبلغ أو تصحيحها يدويًا.';
$string['notconfigured'] = 'بوابة الدفع Flouci غير مهيأة لحساب الدفع هذا.';
$string['notransactions'] = 'لم يتم العثور على جلسات دفع Flouci.';
$string['paymentexpired'] = 'انتهت صلاحية جلسة الدفع ولم يتم خصم أي مبلغ منك. يمكنك المحاولة مرة أخرى.';
$string['paymentfailed'] = 'لم تكتمل عملية الدفع ولم يتم خصم أي مبلغ منك. يمكنك المحاولة مرة أخرى.';
$string['paymentmismatch'] = 'تعذّرت مطابقة دفعتك مع المبلغ المستحق، لذلك لم يتم تسليم مشترياتك بعد. يرجى الاتصال بمسؤول الموقع وذكر المرجع {$a}.';
$string['paymentpending'] = 'لم يتم تأكيد دفعتك بعد. إذا أتممت الدفع فيرجى الانتظار بضع ثوانٍ ثم التحقق مرة أخرى. سيتم تسليم مشترياتك تلقائيًا بمجرد تأكيد Flouci للدفعة.';
$string['paymentsuccessful'] = 'تم استلام الدفعة. شكرًا لك!';
$string['pluginname'] = 'Flouci';
$string['pluginname_desc'] = 'تتيح لك إضافة Flouci استلام المدفوعات بالدينار التونسي (TND) عبر Flouci. أنشئ تطبيقًا في حساب المطوّر لدى Flouci ثم أدخل مفتاحيه العام والخاص في قسم Flouci ضمن حساب الدفع (إدارة الموقع > المدفوعات > حسابات الدفع). استخدم مفاتيح TEST APP أثناء الاختبار ثم مفاتيح الإنتاج.';
$string['privacy:metadata:flouci'] = 'لمعالجة الدفعة، يُرسَل مبلغ ومرجع عشوائي مبهم إلى Flouci. يدخل العميل بعد ذلك بيانات الدفع في موقع Flouci؛ ولا يطّلع Moodle عليها ولا يخزّنها.';
$string['privacy:metadata:flouci:amount'] = 'المبلغ المطلوب دفعه بالميليم.';
$string['privacy:metadata:flouci:trackingid'] = 'مرجع عشوائي ينشئه Moodle ولا يحتوي على أي بيانات شخصية.';
$string['privacy:metadata:paygw_flouci'] = 'يخزّن حالة كل جلسة دفع عبر Flouci.';
$string['privacy:metadata:paygw_flouci:amountmillimes'] = 'المبلغ المخصوم بالميليم.';
$string['privacy:metadata:paygw_flouci:flouciid'] = 'معرّف الدفعة الذي منحته Flouci.';
$string['privacy:metadata:paygw_flouci:item'] = 'ما دفع المستخدم مقابله.';
$string['privacy:metadata:paygw_flouci:ref'] = 'المرجع العشوائي لجلسة الدفع.';
$string['privacy:metadata:paygw_flouci:status'] = 'حالة جلسة الدفع.';
$string['privacy:metadata:paygw_flouci:time'] = 'وقت إنشاء جلسة الدفع.';
$string['privacy:metadata:paygw_flouci:userid'] = 'معرّف المستخدم الذي بدأ عملية الدفع.';
$string['publickey'] = 'المفتاح العام (APP_PUBLIC)';
$string['publickey_help'] = 'المفتاح العام لتطبيقك من لوحة تحكم المطوّر في Flouci. استخدم مفاتيح TEST APP للاختبار ومفاتيح الإنتاج عند الانتقال إلى الوضع الحي.';
$string['redirecting'] = 'جارٍ التحويل إلى Flouci...';
$string['secretkey'] = 'المفتاح الخاص (APP_SECRET)';
$string['secretkey_help'] = 'المفتاح الخاص لتطبيقك من لوحة تحكم المطوّر في Flouci. احتفظ به سرًّا.';
$string['status_complete'] = 'مكتمل';
$string['status_expired'] = 'منتهي الصلاحية';
$string['status_failed'] = 'فشل';
$string['status_mismatch'] = 'غير مطابق (للمراجعة)';
$string['status_paid'] = 'مدفوع، التسليم قيد الانتظار';
$string['status_pending'] = 'قيد الانتظار';
$string['task_cleanup'] = 'Flouci: تنظيف جلسات الدفع القديمة';
$string['task_reconcile'] = 'Flouci: مطابقة المدفوعات المعلّقة';
$string['transactionnotfound'] = 'لم يتم العثور على جلسة الدفع.';
$string['transactions'] = 'معاملات Flouci';
$string['unsupportedcurrency'] = 'تدعم Flouci الدفع بالدينار التونسي (TND) فقط، لكن سعر هذا العنصر محدد بعملة {$a}.';
