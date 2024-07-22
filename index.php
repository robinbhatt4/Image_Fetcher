<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Search and Drop</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .wrapper {
            display: flex;
            flex: 1;
        }
        .sidebar {
            width: 250px;
            position: fixed;
            top: 56px; /* Height of the navbar */
            bottom: 0;
            left: 0;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 1rem;
        }
        .content {
            margin-left: 250px;
            padding: 1rem;
            flex: 1;
        }
        .drop-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background-color: #f8f9fa;
            position: relative;
        }
        .drop-area.dragover {
            background-color: #e9ecef;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        .draggable {
            cursor: move;
        }
        .dropped-image {
            max-width: 150px;
            margin: 5px;
            cursor: pointer;
            position: relative;
            display: inline-block;
        }
        .dropped-image.selected {
            border: 2px solid #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }
        .controls {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
        }
        .controls button {
            margin: 5px 0;
        }
        .image-toolbar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            text-align: center;
            visibility: hidden;
        }
        .dropped-image:hover .image-toolbar {
            visibility: visible;
        }
        .toolbar-button {
            background: transparent;
            border: none;
            color: white;
            margin: 0 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Image Search and Drop</a>
    </nav>

    <div class="wrapper">
        <div class="sidebar">
            <h5>Search for an image:</h5>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                <div class="input-group-append">
                    <button class="btn btn-primary" id="searchButton">Search</button>
                </div>
            </div>
            <div id="searchResults">
                <!-- Search results will be displayed here -->
            </div>
        </div>

        <div class="content">
            <h5>Drop images here:</h5>
            <div class="drop-area" id="dropArea">
                Drag & Drop images here
                <div class="controls">
                    <button class="btn btn-warning" id="selectButton">Select</button>
                    <button class="btn btn-danger" id="deleteButton">Delete</button>
                </div>
            </div>
            <div id="droppedImages" class="mt-3">
                <!-- Dropped images will be displayed here -->
            </div>
        </div>
    </div>

    <script>
        const droppedImagesSet = new Set();
        let selectingMode = false; // Track if select mode is active
        const droppedImagesContainer = document.getElementById('droppedImages');

        document.getElementById('searchButton').addEventListener('click', () => {
            const query = document.getElementById('searchInput').value;
            fetch(`fetch_images.php?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    const searchResults = document.getElementById('searchResults');
                    searchResults.innerHTML = '';
                    data.forEach(url => {
                        const img = document.createElement('img');
                        img.src = url;
                        img.classList.add('img-thumbnail', 'mb-2', 'draggable');
                        img.draggable = true;
                        img.dataset.src = url;
                        img.addEventListener('dragstart', (event) => {
                            event.dataTransfer.setData('text/plain', event.target.dataset.src);
                        });
                        searchResults.appendChild(img);
                    });
                })
                .catch(error => console.error('Error fetching images:', error));
        });

        document.getElementById('dropArea').addEventListener('dragover', (event) => {
            event.preventDefault();
            dropArea.classList.add('dragover');
        });

        document.getElementById('dropArea').addEventListener('dragleave', () => {
            dropArea.classList.remove('dragover');
        });

        document.getElementById('dropArea').addEventListener('drop', (event) => {
            event.preventDefault();
            dropArea.classList.remove('dragover');
            const url = event.dataTransfer.getData('text/plain');
            if (url) {
                const existingImage = event.target.closest('.dropped-image');
                if (existingImage) {
                    existingImage.querySelector('img').src = url;
                } else {
                    if (!droppedImagesSet.has(url)) {
                        droppedImagesSet.add(url);
                        const imgWrapper = document.createElement('div');
                        imgWrapper.classList.add('dropped-image');
                        imgWrapper.dataset.src = url;

                        const img = document.createElement('img');
                        img.src = url;
                        img.classList.add('img-thumbnail');
                        img.style.width = '150px';
                        img.dataset.src = url;

                        const toolbar = document.createElement('div');
                        toolbar.classList.add('image-toolbar');

                        const selectButton = document.createElement('button');
                        selectButton.textContent = 'Select';
                        selectButton.classList.add('toolbar-button');
                        selectButton.addEventListener('click', (e) => {
                            e.stopPropagation();
                            imgWrapper.classList.toggle('selected');
                        });

                        const magnifyButton = document.createElement('button');
                        magnifyButton.textContent = 'Magnify';
                        magnifyButton.classList.add('toolbar-button');
                        magnifyButton.addEventListener('click', (e) => {
                            e.stopPropagation();
                            window.open(url, '_blank');
                        });

                        toolbar.appendChild(selectButton);
                        toolbar.appendChild(magnifyButton);

                        imgWrapper.appendChild(img);
                        imgWrapper.appendChild(toolbar);

                        droppedImagesContainer.appendChild(imgWrapper);
                    } else {
                        console.error('Image already dropped:', url);
                    }
                }
            } else {
                console.error('No URL found in dataTransfer');
            }
        });

        document.getElementById('selectButton').addEventListener('click', () => {
            selectingMode = !selectingMode;
            const button = document.getElementById('selectButton');
            button.textContent = selectingMode ? 'Deselect' : 'Select';
            if (!selectingMode) {
                const selectedImages = droppedImagesContainer.querySelectorAll('.selected');
                selectedImages.forEach(img => img.classList.remove('selected'));
            }
        });

        document.getElementById('deleteButton').addEventListener('click', () => {
            if (selectingMode) {
                const selectedImages = droppedImagesContainer.querySelectorAll('.selected');
                selectedImages.forEach(img => {
                    droppedImagesSet.delete(img.dataset.src);
                    img.remove();
                });
                selectingMode = false;
                document.getElementById('selectButton').textContent = 'Select';
            }
        });
    </script>
</body>
</html>
