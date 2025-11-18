/**
 * MediaMan Pro - Core JavaScript Library
 */

export class MediaManCore {
    constructor(options = {}) {
        this.options = {
            endpoint: '/mediaman/upload',
            validateEndpoint: '/mediaman/validate-license',
            maxSize: 10485760, // 10MB
            chunkSize: 2097152, // 2MB
            ...options
        };

        this.isLicensed = false;
        this.isLocalhost = false;
        this.features = [];
    }

    /**
     * Validate license
     */
    async validateLicense() {
        try {
            const response = await fetch(this.options.validateEndpoint);
            const data = await response.json();

            this.isLicensed = data.is_licensed;
            this.isLocalhost = data.is_localhost;
            this.features = data.features || [];

            return this.isPro();
        } catch (error) {
            console.error('License validation failed:', error);
            return false;
        }
    }

    /**
     * Check if Pro features are available
     */
    isPro() {
        return this.isLicensed || this.isLocalhost;
    }

    /**
     * Upload file
     */
    async upload(file, options = {}) {
        const formData = new FormData();
        formData.append('file', file);

        if (options.collection) {
            formData.append('collection', options.collection);
        }

        if (options.conversions) {
            formData.append('conversions', JSON.stringify(options.conversions));
        }

        if (options.tags) {
            formData.append('tags', JSON.stringify(options.tags));
        }

        if (options.aiFeatures) {
            formData.append('ai_features', JSON.stringify(options.aiFeatures));
        }

        try {
            const response = await fetch(this.options.endpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                }
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Upload failed');
            }

            return await response.json();
        } catch (error) {
            console.error('Upload error:', error);
            throw error;
        }
    }

    /**
     * Upload with progress callback
     */
    uploadWithProgress(file, options = {}, onProgress = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const formData = new FormData();

            formData.append('file', file);
            if (options.collection) formData.append('collection', options.collection);
            if (options.conversions) formData.append('conversions', JSON.stringify(options.conversions));
            if (options.tags) formData.append('tags', JSON.stringify(options.tags));
            if (options.aiFeatures) formData.append('ai_features', JSON.stringify(options.aiFeatures));

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && onProgress) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    onProgress(percentComplete);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    reject(new Error('Upload failed'));
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Upload error'));
            });

            xhr.open('POST', this.options.endpoint);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        });
    }

    /**
     * Format file size
     */
    formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(2)} ${units[unitIndex]}`;
    }

    /**
     * Generate preview for file
     */
    generatePreview(file) {
        return new Promise((resolve) => {
            if (!file.type.startsWith('image/')) {
                resolve(null);
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = () => resolve(null);
            reader.readAsDataURL(file);
        });
    }
}

// Alpine.js component
if (window.Alpine) {
    window.mediamanUploader = (options) => ({
        ...options,
        files: [],
        isDragging: false,
        isLicensed: false,
        isLocalhost: false,
        aiFeatures: {
            autoTag: false,
            extractText: false,
            generateAlt: false
        },

        get isPro() {
            return this.isLicensed || this.isLocalhost;
        },

        async init() {
            const core = new MediaManCore();
            const licensed = await core.validateLicense();
            this.isLicensed = core.isLicensed;
            this.isLocalhost = core.isLocalhost;
        },

        async handleDrop(e) {
            this.isDragging = false;
            const files = Array.from(e.dataTransfer.files);
            await this.processFiles(files);
        },

        async handleFileSelect(e) {
            const files = Array.from(e.target.files);
            await this.processFiles(files);
        },

        async processFiles(fileList) {
            for (const file of fileList) {
                if (file.size > this.maxSize) {
                    alert(`File ${file.name} exceeds maximum size`);
                    continue;
                }

                const preview = await this.generatePreview(file);
                const fileObject = {
                    id: Date.now() + Math.random(),
                    file,
                    progress: 0,
                    status: 'pending',
                    preview
                };

                this.files.push(fileObject);
                this.uploadFile(fileObject);
            }
        },

        async uploadFile(fileObject) {
            const core = new MediaManCore();

            try {
                await core.uploadWithProgress(
                    fileObject.file,
                    {
                        collection: this.collection,
                        conversions: this.conversions,
                        aiFeatures: Object.keys(this.aiFeatures).filter(k => this.aiFeatures[k])
                    },
                    (progress) => {
                        fileObject.progress = progress;
                    }
                );

                fileObject.status = 'success';
                fileObject.progress = 100;
            } catch (error) {
                fileObject.status = 'error';
                console.error('Upload failed:', error);
            }
        },

        async generatePreview(file) {
            const core = new MediaManCore();
            return await core.generatePreview(file);
        },

        removeFile(id) {
            this.files = this.files.filter(f => f.id !== id);
        },

        formatSize(bytes) {
            const core = new MediaManCore();
            return core.formatSize(bytes);
        }
    });
}

export default MediaManCore;
