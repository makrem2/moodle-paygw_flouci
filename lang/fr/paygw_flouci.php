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
 * Strings for component 'paygw_flouci', language 'fr'.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['amount'] = 'Montant';
$string['apierror'] = 'Le service de paiement (Flouci) est injoignable ou a renvoyé une réponse inattendue. Veuillez réessayer dans quelques minutes.';
$string['cannotstart'] = 'Le paiement n\'a pas pu être démarré. Votre compte n\'a pas été débité. Veuillez réessayer ou contacter l\'administrateur du site.';
$string['checkagain'] = 'Vérifier à nouveau';
$string['deliverypending'] = 'Votre paiement a bien été reçu. Votre achat est en cours de finalisation et sera disponible dans un instant.';
$string['event_payment_completed'] = 'Paiement Flouci terminé';
$string['event_payment_mismatch'] = 'Paiement Flouci non conforme';
$string['flouciid'] = 'Identifiant de paiement Flouci';
$string['gatewaydescription'] = 'Flouci vous permet de payer en dinars tunisiens avec votre portefeuille Flouci ou une carte bancaire.';
$string['gatewayname'] = 'Flouci';
$string['id'] = 'ID';
$string['invalidamount'] = 'Le montant à payer n\'est pas valide.';
$string['invalidkey'] = 'La clé ne doit contenir ni espaces ni deux-points.';
$string['item'] = 'Article (composant / zone / id)';
$string['lasterror'] = 'Dernier message';
$string['lockfailed'] = 'Le paiement est en cours de traitement par une autre requête. Veuillez réessayer dans un instant.';
$string['mismatchesfound'] = '{$a} paiement(s) Flouci ont été signalés : le montant ou la référence indiqués par Flouci ne correspondent pas à la demande. La commande n\'a pas été livrée. Veuillez les examiner et rembourser ou corriger manuellement.';
$string['notconfigured'] = 'La passerelle de paiement Flouci n\'est pas configurée pour ce compte de paiement.';
$string['notransactions'] = 'Aucune session de paiement Flouci trouvée.';
$string['paymentexpired'] = 'La session de paiement a expiré et vous n\'avez pas été débité. Vous pouvez réessayer.';
$string['paymentfailed'] = 'Le paiement n\'a pas abouti et vous n\'avez pas été débité. Vous pouvez réessayer.';
$string['paymentmismatch'] = 'Nous n\'avons pas pu faire correspondre votre paiement au montant dû ; votre achat n\'a donc pas encore été livré. Veuillez contacter l\'administrateur du site en indiquant la référence {$a}.';
$string['paymentpending'] = 'Votre paiement n\'est pas encore confirmé. Si vous l\'avez effectué, patientez quelques secondes puis vérifiez à nouveau. Votre achat sera livré automatiquement dès que Flouci aura confirmé le paiement.';
$string['paymentsuccessful'] = 'Paiement reçu. Merci !';
$string['pluginname'] = 'Flouci';
$string['pluginname_desc'] = 'Le plugin Flouci permet de recevoir des paiements en dinars tunisiens (TND) via Flouci. Créez une application dans votre compte développeur Flouci, puis saisissez ses clés publique et privée dans la section Flouci d\'un compte de paiement (Administration du site > Paiements > Comptes de paiement). Utilisez les clés de la TEST APP pour les essais, puis vos clés de production.';
$string['privacy:metadata:flouci'] = 'Pour traiter le paiement, un montant et une référence aléatoire opaque sont envoyés à Flouci. Le client saisit ensuite ses informations de paiement sur le site de Flouci ; Moodle ne les voit ni ne les conserve.';
$string['privacy:metadata:flouci:amount'] = 'Le montant à payer, en millimes.';
$string['privacy:metadata:flouci:trackingid'] = 'Une référence aléatoire générée par Moodle. Elle ne contient aucune donnée personnelle.';
$string['privacy:metadata:paygw_flouci'] = 'Conserve l\'état de chaque session de paiement Flouci.';
$string['privacy:metadata:paygw_flouci:amountmillimes'] = 'Le montant débité, en millimes.';
$string['privacy:metadata:paygw_flouci:flouciid'] = 'L\'identifiant de paiement attribué par Flouci.';
$string['privacy:metadata:paygw_flouci:item'] = 'Ce que l\'utilisateur a payé.';
$string['privacy:metadata:paygw_flouci:ref'] = 'La référence aléatoire de la session de paiement.';
$string['privacy:metadata:paygw_flouci:status'] = 'L\'état de la session de paiement.';
$string['privacy:metadata:paygw_flouci:time'] = 'La date de création de la session de paiement.';
$string['privacy:metadata:paygw_flouci:userid'] = 'L\'identifiant de l\'utilisateur qui a démarré le paiement.';
$string['publickey'] = 'Clé publique (APP_PUBLIC)';
$string['publickey_help'] = 'La clé publique de votre application, disponible dans le tableau de bord développeur Flouci. Utilisez les clés de la TEST APP pour les essais et vos clés de production une fois en ligne.';
$string['redirecting'] = 'Redirection vers Flouci...';
$string['secretkey'] = 'Clé privée (APP_SECRET)';
$string['secretkey_help'] = 'La clé privée de votre application, disponible dans le tableau de bord développeur Flouci. Gardez-la secrète.';
$string['status_complete'] = 'Terminé';
$string['status_expired'] = 'Expiré';
$string['status_failed'] = 'Échoué';
$string['status_mismatch'] = 'Non conforme (à examiner)';
$string['status_paid'] = 'Payé, livraison en attente';
$string['status_pending'] = 'En attente';
$string['task_cleanup'] = 'Flouci : nettoyer les anciennes sessions de paiement';
$string['task_reconcile'] = 'Flouci : rapprocher les paiements en attente';
$string['transactionnotfound'] = 'Session de paiement introuvable.';
$string['transactions'] = 'Transactions Flouci';
$string['unsupportedcurrency'] = 'Flouci ne prend en charge que le TND, mais cet article est facturé en {$a}.';
