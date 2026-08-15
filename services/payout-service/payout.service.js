const axios = require('axios');
const db = require('../shared/db');

class PayoutService {
  async initiateTransfer(payout) {
    const response = await axios.post('https://api.flutterwave.com/v3/transfers', {
      account_bank: payout.bank_code,
      account_number: payout.account_number,
      amount: payout.amount,
      narration: `Tanaki Hub Payout - ${payout.recipient_type}`,
      currency: payout.currency || 'KES',
      reference: payout.idempotency_key,
      callback_url: `${process.env.API_URL}/webhooks/payout`
    }, {
      headers: { Authorization: `Bearer ${process.env.FLW_SECRET_KEY}` }
    });

    return response.data.data;
  }

  async getTransferStatus(id) {
    const response = await axios.get(`https://api.flutterwave.com/v3/transfers/${id}`, {
      headers: { Authorization: `Bearer ${process.env.FLW_SECRET_KEY}` }
    });

    return response.data.data;
  }

  async approvePayout(payoutId, actorId, actorRole) {
    return await db.transaction(async (trx) => {
      const payout = await trx('payouts').where({ id: payoutId }).first();

      if (payout.status !== 'pending') {
        throw new Error('Payout is not in pending state');
      }

      // 1. Audit Approval
      await trx('payout_approvals').insert({
        payout_id: payoutId,
        actor_id: actorId,
        actor_role: actorRole,
        action: 'approved'
      });

      // 2. Update Payout Status
      await trx('payouts').where({ id: payoutId }).update({
        status: 'approved',
        updated_at: trx.fn.now()
      });

      // 3. Move funds to Pending Holds (Atomic with Ledger if needed)
      // This logic would ideally call the WalletService
      const wallet = await trx('wallets')
        .where({ company_id: payout.company_id, type: 'expense' })
        .first();

      await trx('wallets')
        .where({ id: wallet.id, version: wallet.version })
        .update({
          available_balance: wallet.available_balance - BigInt(payout.amount),
          pending_holds: wallet.pending_holds + BigInt(payout.amount),
          version: wallet.version + 1,
          updated_at: trx.fn.now()
        });

      return { success: true };
    });
  }
}

module.exports = new PayoutService();
