/**
 * MediaMan Core - Framework-agnostic JavaScript library
 *
 * @packageDocumentation
 */

export interface MediaManConfig {
  uploadUrl: string;
  collection?: string;
  conversions?: string[];
  tags?: string[];
  disk?: string;
  headers?: Record<string, string>;
  withCredentials?: boolean;
}

export interface UploadOptions {
  onProgress?: (percent: number, loaded: number, total: number) => void;
  onComplete?: (response: MediaResponse) => void;
  onError?: (error: Error | string) => void;
  onAbort?: () => void;
}

export interface MediaResponse {
  id: number;
  uuid?: string;
  name: string;
  file_name: string;
  extension: string;
  mime_type: string;
  size: number;
  disk: string;
  media_url: string;
  media_uri: string;
  created_at: string;
}

export interface BatchUploadResult {
  batchId: string;
  total: number;
  uploaded: number;
  failed: number;
  results: MediaResponse[];
  errors: Array<{ file: string; error: string }>;
}

/**
 * MediaMan Core Client
 */
export class MediaManCore {
  private config: MediaManConfig;
  private xhr: XMLHttpRequest | null = null;

  constructor(config: MediaManConfig) {
    this.config = {
      withCredentials: true,
      ...config,
    };

    if (!this.config.uploadUrl) {
      throw new Error('uploadUrl is required');
    }
  }

  /**
   * Upload single file
   */
  upload(file: File, options: UploadOptions = {}): Promise<MediaResponse> {
    return new Promise((resolve, reject) => {
      const formData = this.buildFormData(file);
      this.xhr = this.createXhr(formData, options, resolve, reject);
      this.xhr.send(formData);
    });
  }

  /**
   * Upload multiple files
   */
  async uploadMultiple(
    files: File[],
    options: UploadOptions = {}
  ): Promise<BatchUploadResult> {
    const results: MediaResponse[] = [];
    const errors: Array<{ file: string; error: string }> = [];
    let uploaded = 0;
    let failed = 0;

    for (let i = 0; i < files.length; i++) {
      try {
        const result = await this.upload(files[i], {
          ...options,
          onProgress: (percent, loaded, total) => {
            const overallPercent = ((i + percent / 100) / files.length) * 100;
            options.onProgress?.(overallPercent, loaded, total);
          },
        });
        results.push(result);
        uploaded++;
      } catch (error) {
        errors.push({
          file: files[i].name,
          error: error instanceof Error ? error.message : String(error),
        });
        failed++;
      }
    }

    const batchResult: BatchUploadResult = {
      batchId: this.generateBatchId(),
      total: files.length,
      uploaded,
      failed,
      results,
      errors,
    };

    if (failed === 0 && options.onComplete) {
      // @ts-ignore - batch result is compatible
      options.onComplete(batchResult);
    } else if (failed > 0 && options.onError) {
      options.onError(new Error(`${failed} file(s) failed to upload`));
    }

    return batchResult;
  }

  /**
   * Abort current upload
   */
  abort(): void {
    if (this.xhr) {
      this.xhr.abort();
      this.xhr = null;
    }
  }

  /**
   * Update configuration
   */
  updateConfig(config: Partial<MediaManConfig>): void {
    this.config = { ...this.config, ...config };
  }

  /**
   * Build FormData from file and config
   */
  private buildFormData(file: File): FormData {
    const formData = new FormData();
    formData.append('file', file);

    if (this.config.collection) {
      formData.append('collection', this.config.collection);
    }

    if (this.config.conversions && this.config.conversions.length > 0) {
      formData.append('conversions', JSON.stringify(this.config.conversions));
    }

    if (this.config.tags && this.config.tags.length > 0) {
      formData.append('tags', JSON.stringify(this.config.tags));
    }

    if (this.config.disk) {
      formData.append('disk', this.config.disk);
    }

    return formData;
  }

  /**
   * Create configured XMLHttpRequest
   */
  private createXhr(
    formData: FormData,
    options: UploadOptions,
    resolve: (value: MediaResponse) => void,
    reject: (reason: any) => void
  ): XMLHttpRequest {
    const xhr = new XMLHttpRequest();

    // Progress handler
    xhr.upload.addEventListener('progress', (e) => {
      if (e.lengthComputable && options.onProgress) {
        const percent = (e.loaded / e.total) * 100;
        options.onProgress(percent, e.loaded, e.total);
      }
    });

    // Load handler (success)
    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const response = JSON.parse(xhr.responseText) as MediaResponse;
          if (options.onComplete) {
            options.onComplete(response);
          }
          resolve(response);
        } catch (error) {
          const err = new Error('Failed to parse response');
          if (options.onError) {
            options.onError(err);
          }
          reject(err);
        }
      } else {
        const err = new Error(`Upload failed with status ${xhr.status}`);
        if (options.onError) {
          options.onError(err);
        }
        reject(err);
      }
    });

    // Error handler
    xhr.addEventListener('error', () => {
      const err = new Error('Upload failed');
      if (options.onError) {
        options.onError(err);
      }
      reject(err);
    });

    // Abort handler
    xhr.addEventListener('abort', () => {
      if (options.onAbort) {
        options.onAbort();
      }
      reject(new Error('Upload aborted'));
    });

    // Setup request
    xhr.open('POST', this.config.uploadUrl);

    // Set headers
    if (this.config.headers) {
      Object.entries(this.config.headers).forEach(([key, value]) => {
        xhr.setRequestHeader(key, value);
      });
    }

    // Set credentials
    xhr.withCredentials = this.config.withCredentials ?? true;

    return xhr;
  }

  /**
   * Generate unique batch ID
   */
  private generateBatchId(): string {
    return `batch_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

/**
 * Create MediaMan instance
 */
export function createMediaMan(config: MediaManConfig): MediaManCore {
  return new MediaManCore(config);
}

export default MediaManCore;
