import React, { useRef, useState, useCallback } from 'react';
import { MediaManCore, type MediaResponse } from '@mediaman/core';

export interface MediaUploaderProps {
  uploadUrl?: string;
  collection?: string;
  conversions?: string[];
  tags?: string[];
  disk?: string;
  multiple?: boolean;
  maxFiles?: number;
  maxFileSize?: number;
  accept?: string;
  dragText?: string;
  subText?: string;
  onUploadComplete?: (media: MediaResponse | MediaResponse[]) => void;
  onUploadProgress?: (percent: number) => void;
  onUploadError?: (error: string) => void;
  onFilesChanged?: (files: MediaResponse[]) => void;
}

export const MediaUploader: React.FC<MediaUploaderProps> = ({
  uploadUrl = '/mediaman/upload',
  collection,
  conversions,
  tags,
  disk,
  multiple = true,
  maxFiles = 10,
  maxFileSize = 10 * 1024 * 1024, // 10MB
  accept = '*',
  dragText,
  subText,
  onUploadComplete,
  onUploadProgress,
  onUploadError,
  onFilesChanged,
}) => {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isDragOver, setIsDragOver] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [uploadedFiles, setUploadedFiles] = useState<MediaResponse[]>([]);
  const [errors, setErrors] = useState<string[]>([]);

  const uploaderRef = useRef(
    new MediaManCore({
      uploadUrl,
      collection,
      conversions,
      tags,
      disk,
    })
  );

  const openFileDialog = () => {
    fileInputRef.current?.click();
  };

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    if (event.target.files) {
      handleFiles(Array.from(event.target.files));
    }
  };

  const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setIsDragOver(false);
    if (event.dataTransfer.files) {
      handleFiles(Array.from(event.dataTransfer.files));
    }
  };

  const handleFiles = async (files: File[]) => {
    setErrors([]);

    // Validate file count
    if (uploadedFiles.length + files.length > maxFiles) {
      setErrors([`Maximum ${maxFiles} files allowed`]);
      return;
    }

    // Validate file sizes
    const oversizedFiles = files.filter((file) => file.size > maxFileSize);
    if (oversizedFiles.length > 0) {
      setErrors([
        `Some files exceed maximum size of ${formatFileSize(maxFileSize)}`,
      ]);
      return;
    }

    setIsUploading(true);
    setUploadProgress(0);

    try {
      if (files.length === 1) {
        const media = await uploaderRef.current.upload(files[0], {
          onProgress: (percent) => {
            setUploadProgress(Math.round(percent));
            onUploadProgress?.(percent);
          },
        });
        const newFiles = [...uploadedFiles, media];
        setUploadedFiles(newFiles);
        onUploadComplete?.(media);
        onFilesChanged?.(newFiles);
      } else {
        const result = await uploaderRef.current.uploadMultiple(files, {
          onProgress: (percent) => {
            setUploadProgress(Math.round(percent));
            onUploadProgress?.(percent);
          },
        });
        const newFiles = [...uploadedFiles, ...result.results];
        setUploadedFiles(newFiles);
        onUploadComplete?.(result.results);
        onFilesChanged?.(newFiles);

        if (result.errors.length > 0) {
          setErrors(result.errors.map((e) => `${e.file}: ${e.error}`));
        }
      }
    } catch (error) {
      const errorMessage =
        error instanceof Error ? error.message : String(error);
      setErrors([errorMessage]);
      onUploadError?.(errorMessage);
    } finally {
      setIsUploading(false);
      setUploadProgress(0);

      // Reset file input
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const removeFile = (id: number) => {
    const newFiles = uploadedFiles.filter((file) => file.id !== id);
    setUploadedFiles(newFiles);
    onFilesChanged?.(newFiles);
  };

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
  };

  return (
    <div className="mediaman-uploader" style={styles.uploader}>
      <div
        className={`mediaman-dropzone ${isDragOver ? 'is-dragover' : ''} ${
          isUploading ? 'is-uploading' : ''
        }`}
        style={{
          ...styles.dropzone,
          ...(isDragOver ? styles.dropzoneHover : {}),
          ...(isUploading ? styles.dropzoneUploading : {}),
        }}
        onClick={openFileDialog}
        onDrop={handleDrop}
        onDragOver={(e) => {
          e.preventDefault();
          setIsDragOver(true);
        }}
        onDragLeave={(e) => {
          e.preventDefault();
          setIsDragOver(false);
        }}
      >
        <input
          ref={fileInputRef}
          type="file"
          multiple={multiple}
          accept={accept}
          onChange={handleFileSelect}
          style={{ display: 'none' }}
        />

        {!isUploading ? (
          <div className="mediaman-dropzone-content" style={styles.dropzoneContent}>
            <svg
              className="mediaman-icon"
              style={styles.icon}
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
              />
            </svg>
            <p className="mediaman-text" style={styles.text}>
              {dragText || 'Click to upload or drag and drop'}
            </p>
            <p className="mediaman-subtext" style={styles.subtext}>
              {subText || 'Supports: Images, Videos, Documents'}
            </p>
          </div>
        ) : (
          <div className="mediaman-upload-progress" style={styles.uploadProgress}>
            <div className="mediaman-progress-bar" style={styles.progressBar}>
              <div
                className="mediaman-progress-fill"
                style={{
                  ...styles.progressFill,
                  width: `${uploadProgress}%`,
                }}
              />
            </div>
            <p className="mediaman-progress-text" style={styles.progressText}>
              {uploadProgress}%
            </p>
          </div>
        )}
      </div>

      {uploadedFiles.length > 0 && (
        <div className="mediaman-uploaded-files" style={styles.uploadedFiles}>
          {uploadedFiles.map((file) => (
            <div
              key={file.id}
              className="mediaman-uploaded-file"
              style={styles.uploadedFile}
            >
              {file.mime_type.startsWith('image/') && (
                <img
                  src={file.media_url}
                  alt={file.name}
                  className="mediaman-thumbnail"
                  style={styles.thumbnail}
                />
              )}
              <div className="mediaman-file-info" style={styles.fileInfo}>
                <p className="mediaman-file-name" style={styles.fileName}>
                  {file.name}
                </p>
                <p className="mediaman-file-size" style={styles.fileSize}>
                  {formatFileSize(file.size)}
                </p>
              </div>
              <button
                onClick={() => removeFile(file.id)}
                className="mediaman-remove-btn"
                style={styles.removeBtn}
                type="button"
              >
                ×
              </button>
            </div>
          ))}
        </div>
      )}

      {errors.length > 0 && (
        <div className="mediaman-errors" style={styles.errors}>
          {errors.map((error, index) => (
            <div key={index} className="mediaman-error" style={styles.error}>
              {error}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// Default styles
const styles: Record<string, React.CSSProperties> = {
  uploader: {
    width: '100%',
  },
  dropzone: {
    border: '2px dashed #cbd5e0',
    borderRadius: '0.5rem',
    padding: '2rem',
    textAlign: 'center',
    cursor: 'pointer',
    transition: 'all 0.2s',
    backgroundColor: '#f7fafc',
  },
  dropzoneHover: {
    borderColor: '#4299e1',
    backgroundColor: '#ebf8ff',
  },
  dropzoneUploading: {
    cursor: 'not-allowed',
  },
  dropzoneContent: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    gap: '0.5rem',
  },
  icon: {
    width: '3rem',
    height: '3rem',
    color: '#4a5568',
  },
  text: {
    margin: 0,
    fontSize: '1rem',
    fontWeight: 500,
    color: '#2d3748',
  },
  subtext: {
    margin: 0,
    fontSize: '0.875rem',
    color: '#718096',
  },
  uploadProgress: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    gap: '0.5rem',
  },
  progressBar: {
    width: '100%',
    height: '0.5rem',
    backgroundColor: '#e2e8f0',
    borderRadius: '9999px',
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#4299e1',
    transition: 'width 0.3s',
  },
  progressText: {
    margin: 0,
    fontSize: '0.875rem',
    fontWeight: 600,
    color: '#2d3748',
  },
  uploadedFiles: {
    marginTop: '1rem',
    display: 'flex',
    flexDirection: 'column',
    gap: '0.5rem',
  },
  uploadedFile: {
    display: 'flex',
    alignItems: 'center',
    gap: '1rem',
    padding: '0.75rem',
    backgroundColor: '#f7fafc',
    borderRadius: '0.5rem',
    border: '1px solid #e2e8f0',
  },
  thumbnail: {
    width: '3rem',
    height: '3rem',
    objectFit: 'cover',
    borderRadius: '0.25rem',
  },
  fileInfo: {
    flex: 1,
  },
  fileName: {
    margin: 0,
    fontSize: '0.875rem',
    fontWeight: 500,
    color: '#2d3748',
  },
  fileSize: {
    margin: 0,
    fontSize: '0.75rem',
    color: '#718096',
  },
  removeBtn: {
    width: '2rem',
    height: '2rem',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    border: 'none',
    backgroundColor: '#fed7d7',
    color: '#c53030',
    borderRadius: '0.25rem',
    cursor: 'pointer',
    fontSize: '1.5rem',
    lineHeight: 1,
    transition: 'background-color 0.2s',
  },
  errors: {
    marginTop: '1rem',
    display: 'flex',
    flexDirection: 'column',
    gap: '0.5rem',
  },
  error: {
    padding: '0.75rem',
    backgroundColor: '#fed7d7',
    color: '#c53030',
    borderRadius: '0.375rem',
    fontSize: '0.875rem',
  },
};

export default MediaUploader;
