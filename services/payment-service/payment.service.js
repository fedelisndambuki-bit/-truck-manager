const axios = require('axios');
const db = require('../shared/db');

class PaymentService {
  async initiatePayment(invoice, company) {
    const txRef = `INV-${invoice.id}-${Date.now()}`;

    const response = await axios.post('https://api.flutterwave.com/v3/payments', {
      tx_ref: txRef,
      amount: invoice.amount,
      currency: invoice.currency || 'KES',
      redirect_url: `${process.env.FRONTEND_URL}/payments/callback`,
      customer: {
        email: invoice.client_email,
        phonenumber: invoice.client_phone,
        name: invoice.client_name
      },
      meta: {
        invoice_id: invoice.id,
        company_id: invoice.company_id
      },
      customizations: {
        title: 'Tanaki Hub Invoice Payment',
        description: `Payment for Invoice #${invoice.number}`,
        logo: 'https://tanaki-hub.com/logo.png'
      }
    }, {
      headers: { Authorization: `Bearer ${process.env.FLW_SECRET_KEY}` }
    });

    // Track the payment in our database
    await db('fin_payments').insert({
      id: require('uuid').v4(),
      company_id: invoice.company_id,
      invoice_id: invoice.id,
      tx_ref: txRef,
      amount: invoice.amount,
      currency: invoice.currency || 'KES',
      status: 'pending',
      created_at: db.fn.now(),
      updated_at: db.fn.now()
    });

    return response.data.data.link;
  }

  async verifyTransaction(transactionId) {
    const response = await axios.get(`https://api.flutterwave.com/v3/transactions/${transactionId}/verify`, {
      headers: { Authorization: `Bearer ${process.env.FLW_SECRET_KEY}` }
    });

    return response.data.data;
  }
}

module.exports = new PaymentService();
