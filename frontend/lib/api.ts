import { ApiResponse } from './types';

const SERVER_API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export async function apiRequest<T>(
  method: HttpMethod,
  path: string,
  body?: Record<string, unknown>,
  token?: string | null,
): Promise<ApiResponse<T>> {
  const normalizedPath = path.startsWith('/api/') ? path : `/api${path}`;
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  const baseUrl = typeof window === 'undefined' ? SERVER_API_BASE_URL : '';

  try {
    const response = await fetch(`${baseUrl}${normalizedPath}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
      cache: 'no-store',
    });

    let data: ApiResponse<T> | null = null;
    const contentType = response.headers.get('content-type') ?? '';
    if (contentType.includes('application/json')) {
      data = (await response.json()) as ApiResponse<T>;
    }

    if (!response.ok) {
      return {
        success: false,
        error: data?.error || data?.message || `Request failed with status ${response.status}`,
      };
    }

    if (data) {
      return data;
    }

    return {
      success: true,
      data: undefined,
    } as ApiResponse<T>;
  } catch (error) {
    if (typeof window !== 'undefined') {
      try {
        return await xhrRequest<T>(`${normalizedPath}`, method, headers, body);
      } catch (xhrError) {
        const message = xhrError instanceof Error ? xhrError.message : 'Failed to fetch';
        return {
          success: false,
          error: `Network error: ${message}`,
        };
      }
    }

    const message = error instanceof Error ? error.message : 'Failed to fetch';
    return {
      success: false,
      error: `Network error: ${message}`,
    };
  }
}

function xhrRequest<T>(
  url: string,
  method: HttpMethod,
  headers: Record<string, string>,
  body?: Record<string, unknown>,
): Promise<ApiResponse<T>> {
  return new Promise((resolve, reject) => {
    const request = new XMLHttpRequest();
    request.open(method, url, true);

    Object.entries(headers).forEach(([key, value]) => {
      request.setRequestHeader(key, value);
    });

    request.onload = () => {
      const contentType = request.getResponseHeader('content-type') ?? '';
      const raw = request.responseText || '';
      const data = contentType.includes('application/json') && raw
        ? (JSON.parse(raw) as ApiResponse<T>)
        : null;

      if (request.status < 200 || request.status >= 300) {
        resolve({
          success: false,
          error: data?.error || data?.message || `Request failed with status ${request.status}`,
        });
        return;
      }

      if (data) {
        resolve(data);
        return;
      }

      resolve({ success: true, data: undefined } as ApiResponse<T>);
    };

    request.onerror = () => {
      reject(new Error('Failed to fetch'));
    };

    request.send(body ? JSON.stringify(body) : undefined);
  });
}
