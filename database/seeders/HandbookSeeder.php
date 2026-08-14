<?php

namespace Database\Seeders;

use App\Models\DemoDocument;
use Illuminate\Database\Seeder;

/**
 * The "company handbook" the RAG playground searches when you do not paste
 * your own text. Deliberately written like a real policy page: long, a bit
 * repetitive, with the useful facts buried in the middle of paragraphs.
 *
 * Documents with a null learner_id are the shared, seeded ones.
 */
class HandbookSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            'Returns and refunds policy' => <<<'TEXT'
            Returns window

            Customers may return most items within 30 days of the delivery date for a full refund. The 30 days
            are counted from the date the carrier marks the parcel as delivered, not from the order date. Items
            bought during a sale period follow the same 30-day window unless the product page said "final sale".

            Condition of returned goods

            Items must come back unused and in their original packaging, with any tags still attached. We will
            accept an item that has been opened and inspected — trying a jacket on is not "used" — but an item
            that has been worn outdoors, washed, or altered cannot be refunded.

            How refunds are issued

            Refunds are issued to the original payment method. Once the returned parcel arrives at our warehouse
            we inspect it within 2 working days and then release the refund. Card refunds usually appear within
            5 to 10 working days depending on the bank; we have no way to speed that up once the refund is
            released. Store credit is issued instantly if the customer prefers it.

            Return shipping costs

            Return shipping is free for customers in the United Kingdom and the European Union — a prepaid label
            is available from the order page. Customers outside those regions pay their own return shipping,
            typically between 12 and 25 USD. If the return is our fault (wrong item sent, item faulty on
            arrival, item damaged in transit) we cover return shipping worldwide and refund the original
            delivery charge as well.

            Damaged or faulty items

            If an item arrives damaged, the customer should photograph the packaging and the item and contact
            support within 14 days. We will send a replacement immediately without waiting for the damaged item
            to come back. Faults that appear later are covered by the manufacturer warranty, which is 24 months
            on electronics and 12 months on everything else.

            Exceptions

            Perishable goods, personalised or engraved items, pierced jewellery and opened software cannot be
            returned unless faulty. Gift cards are non-refundable.
            TEXT,

            'Shipping and delivery' => <<<'TEXT'
            Domestic delivery

            Orders placed before 14:00 on a working day are dispatched the same day. Standard domestic delivery
            takes 2 to 4 working days and is free on orders over 50 USD; below that it is 4.95 USD. Express
            delivery is 12.95 USD and arrives the next working day if the order was placed before the 14:00
            cut-off.

            International delivery

            We ship to 34 countries. International standard delivery takes 7 to 14 working days and costs 19.95
            USD; international express takes 3 to 5 working days and costs 39.95 USD. International delivery is
            never free, regardless of order value. Customers are responsible for any import duty or local taxes
            charged when the parcel enters their country — these are collected by the carrier on delivery and
            are not included in our prices.

            Tracking

            A tracking number is emailed as soon as the carrier scans the parcel, which is usually a few hours
            after dispatch rather than immediately. Until that first scan happens the tracking page will say
            "label created" and show no movement. That is normal and does not mean the parcel is lost.

            Changing an address

            An address can be changed while the order status is "processing" — support can edit it directly.
            Once the status is "shipped" the address is fixed with the carrier and we cannot change it. In that
            case the customer should contact the carrier with the tracking number and request a redirect, or
            wait for the delivery to fail and be returned to us, at which point we refund or re-send.

            Missing parcels

            A domestic parcel is treated as lost if there has been no tracking movement for 7 working days; for
            international it is 21 working days. We then either re-send or refund, at the customer's choice. We
            do not ask the customer to wait longer than that before acting.
            TEXT,

            'Accounts, payments and support hours' => <<<'TEXT'
            Payment methods

            We accept Visa, Mastercard, American Express, PayPal and Apple Pay. We do not accept cheques, bank
            transfers or cash on delivery. A payment is authorised when the order is placed but only captured
            when the order is dispatched, so a cancelled order never becomes a real charge — the authorisation
            drops off the customer's statement within a few days.

            Duplicate charges

            A duplicate charge is almost always a duplicate authorisation rather than two real payments. If the
            customer can see two captured charges for the same order reference, support should refund one
            immediately without waiting for a finance review; the threshold for that discretion is 500 USD.

            Accounts

            Customers can order as a guest. An account is only needed to see order history and save addresses.
            Password resets are self-service from the login page; the reset link is valid for 60 minutes. If a
            customer says they cannot log in, the first thing to check is whether they signed up with a
            different email address than the one they are typing.

            Deleting an account

            Customers may request account deletion at any time and we complete it within 30 days. Order records
            are retained for 7 years for tax reasons even after account deletion, but they are dissociated from
            the customer profile.

            Support hours

            Support is staffed 09:00 to 18:00 UK time Monday to Friday, and 10:00 to 16:00 on Saturdays. We are
            closed on Sundays and UK public holidays. Email is answered within one working day; live chat is
            answered within a few minutes during staffed hours.
            TEXT,
        ];

        foreach ($documents as $title => $body) {
            DemoDocument::updateOrCreate(
                ['title' => $title, 'learner_id' => null],
                ['body' => $body],
            );
        }
    }
}
