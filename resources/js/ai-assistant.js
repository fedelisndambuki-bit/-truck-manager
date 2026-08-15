class AIAssistant {
    constructor() {
        this.apiBaseUrl = '/api';
        this.currentJobId = null;
        this.pollInterval = null;
    }

    /**
     * Ask AI a question
     */
    async askQuestion(question) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/ai/ask`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Authorization': `Bearer ${this.getAuthToken()}`
                },
                body: JSON.stringify({ question })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to submit question');
            }

            this.currentJobId = data.job_id;
            this.startPolling();
            
            return data;
        } catch (error) {
            console.error('Error asking AI:', error);
            throw error;
        }
    }

    /**
     * Poll for AI response
     */
    async pollForResult() {
        if (!this.currentJobId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/ai/result?job_id=${this.currentJobId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.stopPolling();
                return data;
            } else if (response.status === 404) {
                // Still processing, continue polling
                return null;
            } else {
                throw new Error(data.message || 'Failed to get result');
            }
        } catch (error) {
            console.error('Error polling for result:', error);
            this.stopPolling();
            throw error;
        }
    }

    /**
     * Start polling for results
     */
    startPolling() {
        this.stopPolling(); // Clear any existing interval
        
        this.pollInterval = setInterval(async () => {
            try {
                const result = await this.pollForResult();
                if (result) {
                    this.onResult(result);
                }
            } catch (error) {
                this.onError(error);
            }
        }, 2000); // Poll every 2 seconds
    }

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    /**
     * Get AI logs
     */
    async getLogs(filters = {}) {
        const params = new URLSearchParams(filters);
        
        try {
            const response = await fetch(`${this.apiBaseUrl}/ai/logs?${params}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to fetch logs');
            }

            return data;
        } catch (error) {
            console.error('Error fetching logs:', error);
            throw error;
        }
    }

    /**
     * Get AI statistics
     */
    async getStats() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/ai/stats`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to fetch stats');
            }

            return data;
        } catch (error) {
            console.error('Error fetching stats:', error);
            throw error;
        }
    }

    /**
     * Get authentication token
     */
    getAuthToken() {
        // Try to get token from multiple sources
        const token = localStorage.getItem('auth_token') || 
                     sessionStorage.getItem('auth_token') ||
                     document.querySelector('meta[name="api-token"]')?.getAttribute('content');
        
        if (!token) {
            throw new Error('No authentication token found');
        }
        
        return token;
    }

    /**
     * Handle successful result
     */
    onResult(result) {
        // This should be overridden by the implementation
        console.log('AI Response:', result);
    }

    /**
     * Handle error
     */
    onError(error) {
        // This should be overridden by the implementation
        console.error('AI Error:', error);
    }
}

// Example usage implementation
class AIChatWidget {
    constructor() {
        this.ai = new AIAssistant();
        this.init();
    }

    init() {
        // Override result handler
        this.ai.onResult = (result) => this.handleResult(result);
        this.ai.onError = (error) => this.handleError(error);
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        const form = document.getElementById('ai-chat-form');
        const input = document.getElementById('ai-question-input');
        const submitBtn = document.getElementById('ai-submit-btn');
        const loadingIndicator = document.getElementById('ai-loading');
        const responseContainer = document.getElementById('ai-response');

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const question = input.value.trim();
                if (!question) return;

                // Show loading state
                submitBtn.disabled = true;
                loadingIndicator.style.display = 'block';
                responseContainer.innerHTML = '';

                try {
                    await this.ai.askQuestion(question);
                    input.value = '';
                } catch (error) {
                    this.handleError(error);
                    submitBtn.disabled = false;
                    loadingIndicator.style.display = 'none';
                }
            });
        }
    }

    handleResult(result) {
        const submitBtn = document.getElementById('ai-submit-btn');
        const loadingIndicator = document.getElementById('ai-loading');
        const responseContainer = document.getElementById('ai-response');

        submitBtn.disabled = false;
        loadingIndicator.style.display = 'none';

        if (result.success) {
            responseContainer.innerHTML = `
                <div class="ai-response">
                    <div class="ai-response-header">
                        <h4>AI Assistant Response</h4>
                        <small>Response time: ${result.response_time_ms}ms</small>
                    </div>
                    <div class="ai-response-content">
                        ${result.response.replace(/\n/g, '<br>')}
                    </div>
                    <div class="ai-response-footer">
                        <small class="text-muted">Generated at ${new Date().toLocaleString()}</small>
                    </div>
                </div>
            `;
        } else {
            responseContainer.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${result.error}
                </div>
            `;
        }
    }

    handleError(error) {
        const responseContainer = document.getElementById('ai-response');
        responseContainer.innerHTML = `
            <div class="alert alert-danger">
                <strong>Error:</strong> ${error.message}
            </div>
        `;
    }
}

// Initialize the widget when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.aiChatWidget = new AIChatWidget();
});

// Export for use in other modules
window.AIAssistant = AIAssistant;
window.AIChatWidget = AIChatWidget;
