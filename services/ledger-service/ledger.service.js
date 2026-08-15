const db = require('../shared/db');
const { v4: uuidv4 } = require('uuid');

class LedgerService {
  async recordEntry(companyId, referenceType, referenceId, entries, description) {
    const transactionId = uuidv4();

    return await db.transaction(async (trx) => {
      const ledgerEntries = entries.map(entry => ({
        id: uuidv4(),
        transaction_id: transactionId,
        company_id: companyId,
        account_code: entry.accountCode,
        reference_type: referenceType,
        reference_id: referenceId,
        debit: entry.debit || 0,
        credit: entry.credit || 0,
        description: description,
        created_at: trx.fn.now()
      }));

      // Validate Trial Balance (Sum of Debits must equal Sum of Credits)
      const totalDebits = ledgerEntries.reduce((sum, e) => sum + BigInt(e.debit), 0n);
      const totalCredits = ledgerEntries.reduce((sum, e) => sum + BigInt(e.credit), 0n);

      if (totalDebits !== totalCredits) {
        throw new Error('Ledger entry validation failed: Debits do not equal Credits');
      }

      await trx('fin_ledger_entries').insert(ledgerEntries);
      return { transactionId };
    });
  }

  async getTrialBalance(companyId) {
    return await db('fin_ledger_entries')
      .where({ company_id: companyId })
      .select('account_code')
      .sum('debit as total_debit')
      .sum('credit as total_credit')
      .groupBy('account_code');
  }
}

module.exports = new LedgerService();
