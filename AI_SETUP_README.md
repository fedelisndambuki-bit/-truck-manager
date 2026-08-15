# AI Fleet Management System Setup Guide

## Overview
This document provides comprehensive setup instructions for the AI-powered fleet management system integrated into your truck management SaaS platform.

## Features Implemented

### ✅ Multi-Tenancy
- Company-based data isolation using `company_id` columns
- Automatic tenant filtering via `MultiTenantScope` trait
- Secure data access controls

### ✅ Role-Based Access Control
- **Super Admin**: Full access to all AI features and logs
- **Company Admin**: Access to AI features and view logs
- **Fleet Manager**: Access to AI features only
- **Accountant/Driver**: No AI access

### ✅ AI Service Layer
- Production-ready `AIService` class with Ollama integration
- Queue-based processing with `ProcessAIRequest` job
- Comprehensive logging with `ai_logs` table
- Fleet data summarization for AI context

### ✅ Security Features
- Sanctum authentication
- Rate limiting (10 requests per minute)
- Prompt injection prevention
- Request/response logging

### ✅ Performance Optimizations
- Asynchronous job processing
- Fleet data caching (5 minutes)
- Timeout handling (30 seconds)
- Retry mechanism (3 attempts)

## Installation Steps

### 1. Run Database Migrations
```bash
php artisan migrate
```

### 2. Seed Roles and Permissions
```bash
php artisan db:seed --class=RoleSeeder
```

### 3. Create Super Admin User
```bash
php artisan tinker
```
```php
$user = App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'company_id' => 1, // Create a company first
]);
$user->assignRole('Super Admin');
```

### 4. Configure Ollama
Ensure Ollama is running locally:
```bash
# Install Ollama (if not already installed)
curl -fsSL https://ollama.ai/install.sh | sh

# Start Ollama service
ollama serve

# Pull Llama3 model
ollama pull llama3

# Test Ollama
curl http://localhost:11434/api/generate -d '{
  "model": "llama3",
  "prompt": "Hello, world!",
  "stream": false
}'
```

### 5. Configure Queue Worker
Start the queue worker to process AI requests:
```bash
php artisan queue:work --tries=3 --timeout=60
```

For production, use supervisor:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

### 6. Update Frontend Assets
Include the AI assistant JavaScript in your main layout:
```html
<script src="{{ asset('js/ai-assistant.js') }}"></script>
```

## API Endpoints

### AI Assistant
- `POST /api/ai/ask` - Submit a question to AI
- `GET /api/ai/result?job_id={uuid}` - Get AI response

### AI Logs (Admin Only)
- `GET /api/ai/logs` - Get paginated AI logs
- `GET /api/ai/logs/{id}` - Get specific log entry
- `GET /api/ai/stats` - Get AI usage statistics
- `DELETE /api/ai/logs` - Delete log entries

## Frontend Integration

### Basic Usage
```javascript
// Initialize AI Assistant
const ai = new AIAssistant();

// Ask a question
try {
    const result = await ai.askQuestion("How can I improve fuel efficiency?");
    console.log('Job submitted:', result.job_id);
} catch (error) {
    console.error('Error:', error);
}
```

### Advanced Usage with Custom Handlers
```javascript
class MyAIWidget extends AIChatWidget {
    constructor() {
        super();
        // Custom initialization
    }
    
    onResult(result) {
        // Custom result handling
        console.log('AI Response:', result.response);
        // Update UI with response
    }
    
    onError(error) {
        // Custom error handling
        console.error('AI Error:', error);
        // Show error message to user
    }
}

// Initialize custom widget
const myWidget = new MyAIWidget();
```

## Fleet Data Structure

The AI receives summarized fleet data in this format:
```json
{
    "company_name": "Acme Trucking",
    "total_trucks": 25,
    "active_trips": 8,
    "fuel_this_month": 1250.5,
    "maintenance_due_count": 3,
    "top_3_fuel_consuming_trucks": [
        {"plate_number": "ABC-123", "liters_consumed": 450.2},
        {"plate_number": "DEF-456", "liters_consumed": 380.1},
        {"plate_number": "GHI-789", "liters_consumed": 320.8}
    ],
    "driver_risk_summary": {
        "total_drivers": 30,
        "active_drivers": 25,
        "inactive_drivers": 5
    },
    "generated_at": "2026-02-13T14:00:00Z"
}
```

## Security Considerations

### Authentication
- All API endpoints require Sanctum authentication
- Include `Authorization: Bearer {token}` header

### Rate Limiting
- 10 AI requests per minute per user
- Configurable in `routes/api.php`

### Data Isolation
- Multi-tenant scope automatically filters by `company_id`
- Super Admins can access all companies

### Prompt Injection Prevention
- User input is sanitized to prevent system prompt overrides
- Dangerous patterns are filtered out

## Monitoring and Logging

### AI Request Logs
All AI interactions are logged with:
- Company and user ID
- Question and response
- Fleet data context
- Response time
- Success/error status

### Error Handling
- Failed requests are retried up to 3 times
- Detailed error logging for debugging
- Graceful degradation for user experience

## Performance Optimization

### Caching
- Fleet summary data cached for 5 minutes
- Cache automatically invalidated on data changes

### Queue Processing
- AI requests processed asynchronously
- Non-blocking HTTP responses
- Configurable timeout and retry settings

## Troubleshooting

### Common Issues

1. **Ollama Connection Failed**
   ```bash
   # Check if Ollama is running
   curl http://localhost:11434/api/version
   
   # Restart Ollama if needed
   ollama serve
   ```

2. **Queue Worker Not Processing**
   ```bash
   # Check queue status
   php artisan queue:failed
   
   # Restart queue worker
   php artisan queue:restart
   php artisan queue:work
   ```

3. **Permission Denied Errors**
   ```bash
   # Check user roles
   php artisan tinker
   $user = App\Models\User::find(1);
   $user->getRoleNames();
   
   # Assign role if needed
   $user->assignRole('Fleet Manager');
   ```

### Debug Mode
Enable debug logging in `.env`:
```env
LOG_LEVEL=debug
```

## Production Deployment

### Environment Variables
```env
# Ollama Configuration
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=llama3

# Queue Configuration
QUEUE_CONNECTION=redis

# Cache Configuration
CACHE_DRIVER=redis
```

### Supervisor Configuration
Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/truck-manager/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
num_procs=4
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
```

### Monitoring
- Monitor queue failure rates
- Track AI response times
- Set up alerts for Ollama downtime
- Monitor cache hit rates

## Support

For issues and questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check queue failures: `php artisan queue:failed`
3. Verify Ollama connectivity
4. Review user permissions

## Future Enhancements

### Planned Features
- Conversation history support
- Advanced fleet analytics
- Predictive maintenance insights
- Custom AI model training
- Real-time fleet monitoring dashboard

### Scalability Considerations
- Horizontal queue scaling
- Database read replicas
- CDN for static assets
- Load balancer configuration
