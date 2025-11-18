import React, { useState, useEffect, useCallback } from 'react';
import { MediaManCore } from '../core/MediaManCore';

export default function MediaUploader({
  collection = 'default',
  multiple = false,
  conversions = [],
  maxSize = 10485760,
  accept = 'image/*,video/*,application/pdf',
  enableAI = true,
  endpoint = '/mediaman/upload',
  onUploaded,
  onError,
  onProgress
}) {
  const [core] = useState(() => new MediaManCore({ endpoint }));
  const [files, setFiles] = useState([]);
  const [isDragging, setIsDragging] = useState(false);
  const [isLicensed, setIsLicensed] = useState(false);
  const [isLocalhost, setIsLocalhost] = useState(false);
  const [aiFeatures, setAIFeatures] = useState({
    autoTag: false,
    extractText: false,
    generateAlt: false
  });

  const isPro = isLicensed || isLocalhost;

  useEffect(() => {
    const validateLicense = async () => {
      await core.validateLicense();
      setIsLicensed(core.isLicensed);
      setIsLocalhost(core.isLocalhost);
    };
    validateLicense();
  }, [core]);

  const processFiles = useCallback(async (fileList) => {
    for (const file of fileList) {
      if (file.size > maxSize) {
        onError?.({ file, message: 'File size exceeds limit' });
        continue;
      }

      const preview = await core.generatePreview(file);
      const fileObject = {
        id: Date.now() + Math.random(),
        file,
        preview,
        progress: 0,
        status: 'pending'
      };

      setFiles(prev => [...prev, fileObject]);

      try {
        const result = await core.uploadWithProgress(
          file,
          {
            collection,
            conversions,
            aiFeatures: Object.keys(aiFeatures).filter(k => aiFeatures[k])
          },
          (progress) => {
            setFiles(prev => prev.map(f =>
              f.id === fileObject.id ? { ...f, progress } : f
            ));
            onProgress?.({ file: fileObject, progress });
          }
        );

        setFiles(prev => prev.map(f =>
          f.id === fileObject.id ? { ...f, status: 'success', progress: 100 } : f
        ));
        onUploaded?.(result);
      } catch (error) {
        setFiles(prev => prev.map(f =>
          f.id === fileObject.id ? { ...f, status: 'error' } : f
        ));
        onError?.({ file: fileObject, error });
      }
    }
  }, [core, collection, conversions, aiFeatures, maxSize, onUploaded, onError, onProgress]);

  const handleDrop = useCallback((e) => {
    e.preventDefault();
    setIsDragging(false);
    const droppedFiles = Array.from(e.dataTransfer.files);
    processFiles(droppedFiles);
  }, [processFiles]);

  const handleFileSelect = useCallback((e) => {
    const selectedFiles = Array.from(e.target.files);
    processFiles(selectedFiles);
  }, [processFiles]);

  const removeFile = (id) => {
    setFiles(prev => prev.filter(f => f.id !== id));
  };

  return (
    <div className={`mediaman-uploader ${isPro ? 'mediaman-pro' : ''}`}>
      {!isPro && (
        <div className="mediaman-watermark">MediaMan Pro Required</div>
      )}

      <div
        className={`mediaman-dropzone ${isDragging ? 'dragging' : ''}`}
        onDrop={handleDrop}
        onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
        onDragLeave={() => setIsDragging(false)}
        onClick={() => document.getElementById('file-input').click()}
      >
        <input
          id="file-input"
          type="file"
          multiple={multiple}
          accept={accept}
          onChange={handleFileSelect}
          style={{ display: 'none' }}
        />

        <div className="mediaman-dropzone-content">
          <svg className="mediaman-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <p>{isDragging ? 'Drop files here...' : 'Drag & drop files here, or click to browse'}</p>
        </div>
      </div>

      <div className="mediaman-file-list">
        {files.map(file => (
          <div key={file.id} className="mediaman-file-item">
            {file.preview && <img src={file.preview} className="mediaman-thumbnail" alt="" />}
            <div className="mediaman-file-info">
              <p>{file.file.name}</p>
              <p className="text-sm">{core.formatSize(file.file.size)}</p>
            </div>
            <div className="mediaman-file-progress">
              <div className="progress-bar" style={{ width: `${file.progress}%` }}></div>
            </div>
            <span className="status-icon">
              {file.status === 'success' ? '✓' : file.status === 'error' ? '✗' : '⋯'}
            </span>
            <button onClick={() => removeFile(file.id)} className="mediaman-remove-btn">×</button>
          </div>
        ))}
      </div>

      {enableAI && isPro && (
        <div className="mediaman-ai-features">
          <label>
            <input
              type="checkbox"
              checked={aiFeatures.autoTag}
              onChange={(e) => setAIFeatures({ ...aiFeatures, autoTag: e.target.checked })}
            />
            Auto-generate tags
          </label>
          <label>
            <input
              type="checkbox"
              checked={aiFeatures.extractText}
              onChange={(e) => setAIFeatures({ ...aiFeatures, extractText: e.target.checked })}
            />
            Extract text (OCR)
          </label>
          <label>
            <input
              type="checkbox"
              checked={aiFeatures.generateAlt}
              onChange={(e) => setAIFeatures({ ...aiFeatures, generateAlt: e.target.checked })}
            />
            Generate alt text
          </label>
        </div>
      )}

      <style jsx>{`
        .mediaman-uploader { position: relative; }
        .mediaman-watermark {
          position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
          z-index: 1000; background: rgba(0,0,0,0.8); color: white;
          padding: 1rem 2rem; border-radius: 0.5rem; font-weight: bold;
        }
        .mediaman-dropzone {
          border: 2px dashed #cbd5e0; border-radius: 0.5rem;
          padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s;
        }
        .mediaman-dropzone.dragging { border-color: #4299e1; background: #ebf8ff; }
        .mediaman-icon { width: 4rem; height: 4rem; margin: 0 auto 1rem; color: #a0aec0; }
        .mediaman-file-list { margin-top: 1rem; }
        .mediaman-file-item {
          display: flex; align-items: center; gap: 1rem;
          padding: 0.75rem; border: 1px solid #e2e8f0;
          border-radius: 0.375rem; margin-bottom: 0.5rem;
        }
        .mediaman-thumbnail { width: 3rem; height: 3rem; object-fit: cover; border-radius: 0.25rem; }
        .mediaman-file-info { flex: 1; }
        .mediaman-file-progress {
          flex: 1; height: 0.5rem; background: #e2e8f0;
          border-radius: 0.25rem; overflow: hidden;
        }
        .progress-bar { height: 100%; background: #4299e1; transition: width 0.3s; }
        .mediaman-remove-btn {
          background: #f56565; color: white; border: none;
          width: 2rem; height: 2rem; border-radius: 50%;
          cursor: pointer; font-size: 1.5rem; line-height: 1;
        }
        .mediaman-ai-features {
          margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;
        }
      `}</style>
    </div>
  );
}
