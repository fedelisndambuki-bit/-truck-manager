<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Fleet Assistant - Truck Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .ai-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .ai-chat-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .ai-response {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .ai-response-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ai-response-content {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        
        .ai-response-footer {
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 15px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .example-questions {
            margin-top: 20px;
        }
        
        .example-question {
            background: #e9ecef;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .example-question:hover {
            background: #dee2e6;
        }
        
        .stats-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .log-entry {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="ai-container">
                    <h1 class="text-center mb-4">
                        <i class="fas fa-robot"></i> AI Fleet Assistant
                    </h1>
                    
                    <!-- AI Chat Form -->
                    <div class="ai-chat-form">
                        <h5>Ask About Your Fleet</h5>
                        <form id="ai-chat-form">
                            <div class="mb-3">
                                <label for="ai-question-input" class="form-label">Your Question:</label>
                                <textarea 
                                    class="form-control" 
                                    id="ai-question-input" 
                                    rows="3" 
                                    placeholder="e.g., How can I improve fuel efficiency across my fleet?"
                                    required
                                ></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" id="ai-submit-btn">
                                <i class="fas fa-paper-plane"></i> Ask AI
                            </button>
                        </form>
                        
                        <!-- Example Questions -->
                        <div class="example-questions">
                            <h6>Example Questions:</h6>
                            <div class="example-question" onclick="setQuestion('What are my top fuel-consuming trucks this month?')">
                                <i class="fas fa-gas-pump"></i> What are my top fuel-consuming trucks this month?
                            </div>
                            <div class="example-question" onclick="setQuestion('How many trucks are currently in transit?')">
                                <i class="fas fa-truck"></i> How many trucks are currently in transit?
                            </div>
                            <div class="example-question" onclick="setQuestion('Which trucks need maintenance soon?')">
                                <i class="fas fa-wrench"></i> Which trucks need maintenance soon?
                            </div>
                            <div class="example-question" onclick="setQuestion('What is my fleet\'s fuel consumption trend?')">
                                <i class="fas fa-chart-line"></i> What is my fleet's fuel consumption trend?
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading Indicator -->
                    <div class="loading-spinner" id="ai-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </div>
                        <p class="mt-2">AI is analyzing your fleet data...</p>
                    </div>
                    
                    <!-- Response Container -->
                    <div id="ai-response"></div>
                    
                    <!-- AI Statistics -->
                    <div class="row mt-4" id="ai-stats-container">
                        <div class="col-12">
                            <h5>AI Usage Statistics</h5>
                            <div class="row" id="stats-content">
                                <!-- Stats will be loaded here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Logs -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Recent AI Interactions</h5>
                            <div id="recent-logs">
                                <!-- Logs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AI Assistant Script -->
    <script>
        // Helper function to set question in textarea
        function setQuestion(question) {
            document.getElementById('ai-question-input').value = question;
        }
        
        // Enhanced AI Chat Widget with stats and logs
        class EnhancedAIChatWidget extends AIChatWidget {
            constructor() {
                super();
                this.loadStats();
                this.loadRecentLogs();
            }
            
            async loadStats() {
                try {
                    const stats = await this.ai.getStats();
                    this.displayStats(stats.data);
                } catch (error) {
                    console.error('Failed to load stats:', error);
                }
            }
            
            async loadRecentLogs() {
                try {
                    const logs = await this.ai.getLogs({ limit: 5 });
                    this.displayLogs(logs.data);
                } catch (error) {
                    console.error('Failed to load logs:', error);
                }
            }
            
            displayStats(stats) {
                const container = document.getElementById('stats-content');
                container.innerHTML = `
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-primary">${stats.total_requests}</h3>
                            <small>Total Requests</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-success">${stats.successful_requests}</h3>
                            <small>Successful</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-info">${Math.round(stats.avg_response_time || 0)}ms</h3>
                            <small>Avg Response</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-warning">${stats.requests_this_month}</h3>
                            <small>This Month</small>
                        </div>
                    </div>
                `;
            }
            
            displayLogs(logs) {
                const container = document.getElementById('recent-logs');
                
                if (logs.length === 0) {
                    container.innerHTML = '<p class="text-muted">No recent AI interactions.</p>';
                    return;
                }
                
                container.innerHTML = logs.map(log => `
                    <div class="log-entry">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${log.user_name}</strong>
                                <p class="mb-1">${log.question}</p>
                                <small class="text-muted">${new Date(log.created_at).toLocaleString()}</small>
                            </div>
                            <span class="badge bg-${log.status === 'success' ? 'success' : 'danger'}">
                                ${log.status}
                            </span>
                        </div>
                    </div>
                `).join('');
            }
            
            handleResult(result) {
                super.handleResult(result);
                // Refresh stats and logs after successful interaction
                setTimeout(() => {
                    this.loadStats();
                    this.loadRecentLogs();
                }, 1000);
            }
        }
        
        // Initialize the enhanced widget when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            window.aiChatWidget = new EnhancedAIChatWidget();
        });
    </script>
    
    <!-- Include the AI Assistant script -->
    <script src="{{ asset('js/ai-assistant.js') }}"></script>
</body>
</html>
