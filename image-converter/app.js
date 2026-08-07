document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const controlsSection = document.getElementById('controls-section');
    const resultSection = document.getElementById('result-section');
    
    const imagePreview = document.getElementById('image-preview');
    const qualitySlider = document.getElementById('quality-slider');
    const qualityValue = document.getElementById('quality-value');
    const convertBtn = document.getElementById('convert-btn');
    const resetBtn = document.getElementById('reset-btn');
    
    const downloadLink = document.getElementById('download-link');
    const sizeOriginal = document.getElementById('size-original');
    const sizeNew = document.getElementById('size-new');
    const resetBtn2 = document.getElementById('reset-btn-2');
    
    const ratioButtons = document.querySelectorAll('.ratio-btn');

    let currentFile = null;
    let originalSize = 0;
    let cropper = null;

    // Format bytes to readable string
    const formatBytes = (bytes, decimals = 2) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    };

    // Handle Drag & Drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('dragover');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    });

    // Handle Click to upload
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;
        
        const file = files[0];
        
        if (!file.type.match('image.*')) {
            alert('Por favor, selecciona un archivo de imagen válido (JPG, PNG).');
            return;
        }

        currentFile = file;
        originalSize = file.size;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            // Destroy existing cropper if any
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            imagePreview.src = e.target.result;
            
            // Re-initialize active ratio to 'Libre'
            ratioButtons.forEach(b => b.classList.remove('active'));
            document.querySelector('.ratio-btn[data-ratio="NaN"]').classList.add('active');

            showControls();
            
            // Initialize Cropper JS once the image is loaded visually
            imagePreview.onload = () => {
                cropper = new Cropper(imagePreview, {
                    viewMode: 2, // restrict crop box to not exceed size of canvas
                    aspectRatio: NaN, // free crop
                    background: false,
                    zoomable: false,
                });
            };
        };
        reader.readAsDataURL(file);
    }

    function showControls() {
        dropZone.classList.add('hidden');
        controlsSection.classList.remove('hidden');
        resultSection.classList.add('hidden');
    }

    function resetApp() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        currentFile = null;
        fileInput.value = '';
        dropZone.classList.remove('hidden');
        controlsSection.classList.add('hidden');
        resultSection.classList.add('hidden');
    }

    // Aspect Ratio Buttons
    ratioButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            ratioButtons.forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            const ratio = parseFloat(e.target.dataset.ratio);
            if (cropper) {
                cropper.setAspectRatio(ratio);
            }
        });
    });

    // Update quality value display
    qualitySlider.addEventListener('input', (e) => {
        qualityValue.textContent = `${e.target.value}%`;
    });

    // Reset buttons
    resetBtn.addEventListener('click', resetApp);
    resetBtn2.addEventListener('click', resetApp);

    // Convert Image
    convertBtn.addEventListener('click', () => {
        if (!currentFile || !cropper) return;

        const quality = parseInt(qualitySlider.value) / 100;
        const activeBtn = document.querySelector('.ratio-btn.active');
        
        let cropOptions = {
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        };

        // If active button has fixed dimensions (like Banner), force output size
        if (activeBtn && activeBtn.dataset.width && activeBtn.dataset.height) {
            cropOptions.width = parseInt(activeBtn.dataset.width, 10);
            cropOptions.height = parseInt(activeBtn.dataset.height, 10);
        }
        
        // Get cropped image canvas
        const canvas = cropper.getCroppedCanvas(cropOptions);
        
        if (!canvas) {
            alert("Error al procesar el recorte.");
            return;
        }

        // Convert to WebP
        canvas.toBlob((blob) => {
            if (blob) {
                const url = URL.createObjectURL(blob);
                
                // Set download link
                downloadLink.href = url;
                
                // Generate new filename
                const oldName = currentFile.name;
                const newName = oldName.substring(0, oldName.lastIndexOf('.')) + '.webp';
                downloadLink.download = newName;
                
                // Update stats
                sizeOriginal.textContent = formatBytes(originalSize);
                sizeNew.textContent = formatBytes(blob.size);
                
                // Update UI
                controlsSection.classList.add('hidden');
                resultSection.classList.remove('hidden');
            } else {
                alert('Hubo un error al convertir la imagen.');
            }
        }, 'image/webp', quality);
    });
});
