const db = require('../shared/db');

class WalletService {
  async getBalances(companyId) {
    return await db('fin_wallets').where({ company_id: companyId });
  }

  async getBalanceByType(companyId, type) {
    return await db('fin_wallets').where({ company_id: companyId, type }).first();
  }

  async internalTransfer(companyId, fromType, toType, amount, transactionId, description) {
    return await db.transaction(async (trx) => {
      // 1. Debit Source Wallet
      const source = await trx('fin_wallets')
        .where({ company_id: companyId, type: fromType })
        .first();

      if (!source || source.available_balance < amount) {
        throw new Error('Insufficient funds in source wallet');
      }

      const updatedSource = await trx('fin_wallets')
        .where({ id: source.id, version: source.version })
        .update({
          available_balance: source.available_balance - amount,
          ledger_balance: source.ledger_balance - amount,
          version: source.version + 1,
          updated_at: trx.fn.now()
        });

      if (!updatedSource) throw new Error('Source wallet update failed (optimistic lock)');

      // 2. Credit Destination Wallet
      const dest = await trx('fin_wallets')
        .where({ company_id: companyId, type: toType })
        .first();

      if (!dest) throw new Error('Destination wallet not found');

      const updatedDest = await trx('fin_wallets')
        .where({ id: dest.id, version: dest.version })
        .update({
          available_balance: dest.available_balance + amount,
          ledger_balance: dest.ledger_balance + amount,
          version: dest.version + 1,
          updated_at: trx.fn.now()
        });

      if (!updatedDest) throw new Error('Destination wallet update failed (optimistic lock)');

      return { success: true };
    });
  }
}

module.exports = new WalletService();
