/**
 * PHP Backend API Client
 * Handles all HTTP communication with the PHP backend API
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export interface ApiResponse<T = Record<string, unknown>> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
}

export interface User {
  id: string;
  email: string;
  name: string;
  role: 'EMPLOYEE' | 'MANAGER' | 'OWNER';
}

class ApiClient {
  private accessToken: string | null = null;
  private refreshToken: string | null = null;

  /**
   * Get current access token from localStorage
   */
  private getStoredTokens() {
    if (typeof window === 'undefined') return { accessToken: null, refreshToken: null };
    const accessToken = localStorage.getItem('accessToken');
    const refreshToken = localStorage.getItem('refreshToken');
    return { accessToken, refreshToken };
  }

  /**
   * Store tokens in localStorage
   */
  private storeTokens(accessToken: string, refreshToken: string) {
    if (typeof window !== 'undefined') {
      localStorage.setItem('accessToken', accessToken);
      localStorage.setItem('refreshToken', refreshToken);
      this.accessToken = accessToken;
      this.refreshToken = refreshToken;
    }
  }

  /**
   * Clear stored tokens
   */
  clearTokens() {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('accessToken');
      localStorage.removeItem('refreshToken');
    }
    this.accessToken = null;
    this.refreshToken = null;
  }

  /**
   * Make HTTP request with auth header
   */
  private async request<T = Record<string, unknown>>(
    method: string,
    endpoint: string,
    body?: Record<string, unknown>,
  ): Promise<ApiResponse<T>> {
    const { accessToken } = this.getStoredTokens();
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
    };

    if (accessToken) {
      headers.Authorization = `Bearer ${accessToken}`;
    }

    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
      });

      const data = (await response.json()) as ApiResponse<T>;

      if (!response.ok) {
        if (response.status === 401 && this.refreshToken) {
          // Try to refresh token
          await this.refreshAccessToken();
          // Retry the request with new token
          return this.request(method, endpoint, body);
        }
        throw new Error(data.error || data.message || 'API request failed');
      }

      return data;
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error',
      };
    }
  }

  /**
   * Login user and store tokens
   */
  async login(email: string, password: string): Promise<ApiResponse<{ user: User; tokens: AuthTokens }>> {
    const response = await this.request<{ user: User; tokens: AuthTokens }>('POST', '/auth/login', {
      email,
      password,
    });

    if (response.success && response.data?.tokens) {
      this.storeTokens(response.data.tokens.accessToken, response.data.tokens.refreshToken);
    }

    return response;
  }

  /**
   * Register new user
   */
  async signup(email: string, password: string, name: string): Promise<ApiResponse<{ user: User; tokens: AuthTokens }>> {
    const response = await this.request<{ user: User; tokens: AuthTokens }>('POST', '/auth/signup', {
      email,
      password,
      name,
    });

    if (response.success && response.data?.tokens) {
      this.storeTokens(response.data.tokens.accessToken, response.data.tokens.refreshToken);
    }

    return response;
  }

  /**
   * Refresh access token
   */
  async refreshAccessToken(): Promise<ApiResponse<AuthTokens>> {
    const { refreshToken } = this.getStoredTokens();

    if (!refreshToken) {
      return { success: false, error: 'No refresh token available' };
    }

    const response = await this.request<AuthTokens>('POST', '/auth/refresh', {
      refreshToken,
    });

    if (response.success && response.data?.accessToken) {
      this.storeTokens(response.data.accessToken, refreshToken);
    }

    return response;
  }

  /**
   * Get current user profile
   */
  async getCurrentUser(): Promise<ApiResponse<User>> {
    return this.request<User>('GET', '/auth/me');
  }

  /**
   * Logout user
   */
  async logout(): Promise<void> {
    this.clearTokens();
  }

  /**
   * Get all tasks
   */
  async getTasks(): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/tasks');
  }

  /**
   * Create task
   */
  async createTask(title: string, description: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/tasks', {
      title,
      description,
    });
  }

  /**
   * Get task by ID
   */
  async getTask(id: string): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/tasks/${id}`);
  }

  /**
   * Update task
   */
  async updateTask(id: string, updates: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/tasks/${id}`, updates);
  }

  /**
   * Create analysis
   */
  async createAnalysis(taskId: string, analysisData: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/analyses', {
      taskId,
      ...analysisData,
    });
  }

  /**
   * Get analysis
   */
  async getAnalysis(id: string): Promise<ApiResponse<unknown>> {
    return this.request('GET', `/analyses/${id}`);
  }

  /**
   * Update analysis
   */
  async updateAnalysis(id: string, updates: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('PUT', `/analyses/${id}`, updates);
  }

  /**
   * Submit analysis
   */
  async submitAnalysis(id: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/analyses/${id}/submit`, {});
  }

  /**
   * Review analysis (manager)
   */
  async reviewAnalysis(
    id: string,
    action: 'approve' | 'reject',
    feedback?: string,
  ): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/analyses/${id}/review`, {
      action,
      feedback,
    });
  }

  /**
   * Create report
   */
  async createReport(analysisId: string, reportData: Record<string, unknown>): Promise<ApiResponse<unknown>> {
    return this.request('POST', '/reports', {
      analysisId,
      ...reportData,
    });
  }

  /**
   * Finalize report
   */
  async finalizeReport(id: string): Promise<ApiResponse<unknown>> {
    return this.request('POST', `/reports/${id}/finalize`, {});
  }

  /**
   * Get all reports (owner)
   */
  async getReports(): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/reports');
  }

  /**
   * Health check
   */
  async health(): Promise<ApiResponse<unknown>> {
    return this.request('GET', '/health');
  }
}

// Export singleton instance
export const apiClient = new ApiClient();

// Export for server-side usage (optional)
export async function callPhpApi(
  method: string,
  endpoint: string,
  body?: Record<string, unknown>,
  accessToken?: string,
): Promise<ApiResponse> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
  };

  if (accessToken) {
    headers.Authorization = `Bearer ${accessToken}`;
  }

  try {
    const response = await fetch(`${API_URL}${endpoint}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });

    return (await response.json()) as ApiResponse;
  } catch (error) {
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Network error',
    };
  }
}
