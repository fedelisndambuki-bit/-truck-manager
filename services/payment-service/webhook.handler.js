const fastify = require('fastify')({ logger: true });
const paymentService = require('./payment.service');
const db = require('../shared/db');
const ledgerService = require('../ledger-service/ledger.service');

// Secret for webhook signature validation
const FLW_WEBHOOK_HASH = process.env.FLW_WEBHOOK_HASH;

fastify.post('/webhooks/flutterwave', async (request, reply) => {
  const signature = request.headers['verif-hash'];
  if (!signature || signature !== FLW_WEBHOOK_HASH) {
    return reply.status(401).send({ error: 'Unauthorized' });
  }

  const payload = request.body;
  const eventId = payload.id;

  // 1. Idempotency Check & Store Event
  try {
    await db('fin_webhook_events').insert({
      id: require('uuid').v4(),
      source: 'flutterwave',
      event_id: eventId.toString(),
      payload: JSON.stringify(payload),
      status: 'pending',
      created_at: db.fn.now()
    });
  } catch (err) {
    if (err.code === '23505' || err.message.includes('unique')) { // Unique violation
      return reply.status(200).send({ message: 'Duplicate event ignored' });
    }
    throw err;
  }

  // 2. Process Asynchronously
  setImmediate(async () => {
    try {
      const verification = await paymentService.verifyTransaction(payload.data.id);
      
      if (verification.status === 'successful') {
        await db.transaction(async (trx) => {
          // Update Payment Status
          await trx('fin_payments')
            .where({ flw_tx_id: verification.id.toString() })
            .orWhere({ tx_ref: verification.tx_ref })
            .update({
              status: 'completed',
              flw_tx_id: verification.id.toString(),
              updated_at: trx.fn.now()
            });

          // Update Ledger
          const companyId = verification.meta.company_id;
          const invoiceId = verification.meta.invoice_id;
          const amount = BigInt(verification.amount);
          const fee = BigInt(verification.app_fee || 0);
          const netAmount = amount - fee;

          await ledgerService.recordEntry(
            companyId,
            'invoice',
            invoiceId,
            [
              { accountCode: 1001, debit: Number(amount) },
              { accountCode: 3001, credit: Number(netAmount) },
              { accountCode: 2001, credit: Number(fee) }
            ],
            `Payment for Invoice #${invoiceId} via Flutterwave`
          );

          // Update Wallet
          const revenueWallet = await trx('fin_wallets')
            .where({ company_id: companyId, type: 'revenue' })
            .first();

          await trx('fin_wallets')
            .where({ id: revenueWallet.id, version: revenueWallet.version })
            .update({
              available_balance: Number(BigInt(revenueWallet.available_balance) + netAmount),
              ledger_balance: Number(BigInt(revenueWallet.ledger_balance) + netAmount),
              version: revenueWallet.version + 1,
              updated_at: trx.fn.now()
            });

          // Mark Webhook as Processed
          await trx('fin_webhook_events')
            .where({ event_id: eventId.toString() })
            .update({ status: 'processed' });
        });
      }
    } catch (error) {
      console.error('Webhook processing error:', error);
      await db('fin_webhook_events')
        .where({ event_id: eventId.toString() })
        .update({ status: 'failed', error: error.message });
    }
  });

  return reply.status(200).send({ message: 'ACK' });
});

module.exports = fastify;
