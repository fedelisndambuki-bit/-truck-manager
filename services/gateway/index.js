const fastify = require('fastify')({ logger: true });
const auth = require('./middleware/auth');
const rbac = require('./middleware/rbac');

// Register Middleware
fastify.decorateRequest('user', null);
fastify.addHook('preHandler', auth.verifyJWT);

// Wallet Endpoints
fastify.get('/api/wallets', async (req, reply) => {
  const { companyId } = req.user;
  const wallets = await require('./services/wallet-service/wallet.service').getBalances(companyId);
  return wallets;
});

// Payment Endpoints
fastify.post('/api/payments/initiate', async (req, reply) => {
  const { invoice, company } = req.body;
  const link = await require('./services/payment-service/payment.service').initiatePayment(invoice, company);
  return { link };
});

// Payout Endpoints
fastify.post('/api/payouts/approve', {
  preHandler: [rbac.checkPermission('FINANCE_MANAGER')]
}, async (req, reply) => {
  const { payoutId } = req.body;
  const { userId, role } = req.user;
  await require('./services/payout-service/payout.service').approvePayout(payoutId, userId, role);
  return { success: true };
});

// Start Server
fastify.listen({ port: process.env.PORT || 3000 }, (err) => {
  if (err) {
    fastify.log.error(err);
    process.exit(1);
  }
});
