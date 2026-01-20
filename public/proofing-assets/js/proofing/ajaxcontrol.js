    document.addEventListener("DOMContentLoaded", function() {

        const lazyImages = [].slice.call(document.querySelectorAll("img.lazyload"));

        if ("IntersectionObserver" in window) {
          const lazyImageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
              if (entry.isIntersecting) {
                const lazyImage = entry.target;
                lazyImage.src = lazyImage.dataset.src;
  
                lazyImage.onload = function() {
                  lazyImage.classList.add("loaded");
                };
  
                lazyImageObserver.unobserve(lazyImage);
              }
            });
          }, {
            rootMargin: "300px 0px", // preload 300px before viewport
            threshold: 0.1
          });
  
          lazyImages.forEach(function(lazyImage) {
            lazyImageObserver.observe(lazyImage);
          });
  
        } else {
          // --- Fallback for older browsers ---
          lazyImages.forEach(function(lazyImage) {
            lazyImage.src = lazyImage.dataset.src;
            lazyImage.onload = function() {
              lazyImage.classList.add("loaded");
            };
          });
        }
        

        //loading folderquestions
        if (typeof folderQuestions !== 'undefined') {
            folderQuestions.forEach(function(id) {
                toggleValidationMessage(id);
            });
        }
        
        //Previous and Next Button in Proofing
        var validateStep1 = document.getElementById('ValidateStep1');
        var validateStep2 = document.getElementById('ValidateStep2');
        var validateStep3 = document.getElementById('ValidateStep3');
        var validateStep4 = document.getElementById('ValidateStep4');
        if (validateStep1) {
            // Show the buttons with id backPage and classNext
            var backPageButton = document.getElementById('backPage');
            var classNextButton = document.getElementById('classNext');
            if (backPageButton) {
                backPageButton.style.display = 'inline-block';
            }
            if (classNextButton) {
                classNextButton.style.display = 'inline-block';
            }
        }
        if (validateStep2) {
            // Show the buttons with id backPage and classNext
            var subjectPreviousButton = document.getElementById('subjectPrevious');
            var subjectNextButton = document.getElementById('subjectNext');
            if (subjectPreviousButton) {
                subjectPreviousButton.style.display = 'inline-block';
            }
            if (subjectNextButton) {
                subjectNextButton.style.display = 'inline-block';
            }
        }
        if (validateStep3) {
            // Show the buttons with id backPage and classNext
            var groupPreviousButton = document.getElementById('groupPrevious');
            var groupNextButton = document.getElementById('groupNext');
            if (groupPreviousButton) {
                groupPreviousButton.style.display = 'inline-block';
            }
            if (groupNextButton) {
                groupNextButton.style.display = 'inline-block';
            }
        }
        if (validateStep4) {
            // Show the buttons with id backPage and classNext
            var submitPreviousButton = document.getElementById('submitPrevious');
            var submitNextButton = document.getElementById('submitProofingDisabled');
            if (submitPreviousButton) {
                submitPreviousButton.style.display = 'inline-block';
            }
            if (submitNextButton) {
                submitNextButton.style.display = 'inline-block';
            }
        }

    });


/****************************************************************************************** Group Image *****************************************************************************************************************/

$(document).ready(function () {

    //run the default Zoom
    groupImageZoomClick();

    // Initial method to call to apply PanZoom to elements given a selector
    function PanZoom(selector, opts) {
        opts = opts || {};
        let minScale = (opts.minScale ? opts.minScale : 0.1);
        let maxScale = (opts.maxScale ? opts.maxScale : 5);
        let increment = (opts.increment ? opts.increment : 0.05);
        let liner = (opts.liner ? opts.liner : false);
        document.querySelectorAll(selector).forEach(function (ele) {
            new AttachPanZoom(ele, minScale, maxScale, increment, liner);
        });
    }

    // Apply PanZoom functionality to a given element, allow user defined zoom min and inc per scroll
    function AttachPanZoom(ele, minScale, maxScale, increment, liner) {
        this.increment = increment;
        this.minScale = minScale;
        this.maxScale = maxScale;
        this.liner = liner;
        this.panning = false;
        this.oldX = this.oldY = 0;
        let self = this;
        ele.style.transform = "matrix(1, 0, 0, 1, 0, 0)";

        // Gets the current Scale, along with transX and transY
        this.getTransformMatrix = function () {
            let trans = ele.style.transform;
            let start = trans.indexOf("(") + 1;
            let end = trans.indexOf(")");
            let matrix = trans.slice(start, end).split(",");
            return {
                "scale": +matrix[0],
                "transX": +matrix[4],
                "transY": +matrix[5]
            }
        };

        // Given the scale, translateX and translateY apply to CSS transform
        this.setTransformMatrix = function (o) {
            ele.style.transform = 'matrix(' + o.scale + ', 0, 0, ' + o.scale + ', ' + o.transX + ', ' + o.transY + ')';
        };

        this.applyTranslate = function (dx, dy) {
            let newTrans = this.getTransformMatrix();
            newTrans.transX += dx;
            newTrans.transY += dy;
            this.setTransformMatrix(newTrans);
        };

        // Applying Deltas to Scale and Translate transformations
        this.applyScale = function (dscale, x, y) {
            let newTrans = this.getTransformMatrix();
            let tranX = x - (ele.width / 2);
            let tranY = y - (ele.height / 2);
            dscale = (this.liner ? dscale : dscale * (newTrans.scale)); // scale either liner or non-liner
            newTrans.scale += dscale;
            let maxOrMinScale = (newTrans.scale <= this.minScale || newTrans.scale >= this.maxScale);
            if (newTrans.scale < this.minScale) newTrans.scale = this.minScale;
            if (newTrans.scale > this.maxScale) newTrans.scale = this.maxScale;
            if (!maxOrMinScale) {
                this.applyTranslate(tranX, tranY);
                this.setTransformMatrix(newTrans);
                this.applyTranslate(-(tranX * dscale), -(tranY * dscale));
            }
        };

        // Capture when the mouse is down on the element or not
        ele.addEventListener("mousedown", function (e) {
            e.preventDefault();
            this.panning = true;
            this.oldX = e.clientX;
            this.oldY = e.clientY;
        });

        ele.addEventListener("mouseup", function (e) {
            this.panning = false;
        });
        ele.addEventListener("mouseleave", function (e) {
            this.panning = false;
        });

        ele.addEventListener("mousemove", function (e) {
            if (this.panning) {
                let deltaX = e.clientX - this.oldX;
                let deltaY = e.clientY - this.oldY;
                self.applyTranslate(deltaX, deltaY);
                this.oldX = e.clientX;
                this.oldY = e.clientY;
            }
        });

        this.getScrollDirection = function (e) {
            let delta = (e.wheelDelta ? e.wheelDelta : e.deltaY * -1);
            if (delta < 0)
                self.applyScale(-self.increment, e.offsetX, e.offsetY);
            else
                self.applyScale(self.increment, e.offsetX, e.offsetY);
        };

        this.stopScrollingPage = function (e) {
            e.preventDefault();
        };

        // Adding the passive option to the event listeners
        ele.addEventListener('DOMMouseScroll', this.getScrollDirection, { passive: true });
        ele.addEventListener('mousewheel', this.getScrollDirection, { passive: true });
        ele.addEventListener('wheel', this.getScrollDirection, { passive: true });

        ele.addEventListener('DOMMouseScroll', this.stopScrollingPage, { passive: true });
        ele.addEventListener('mousewheel', this.stopScrollingPage, { passive: true });
        ele.addEventListener('wheel', this.stopScrollingPage, { passive: true });

    }

    //reset the size and translate of the PZ Image
    function resetPzImage() {
        var groupImageZoomPz = $('img.group-image-zoom-pz');
        var groupImageZoomPzHolder = $('div.group-image-zoom-pz-holder');
        var pzImageClientSizeW;
        var pzImageClientSizeH;
        var pzHolderSizeW;
        var pzHolderSizeH;
        var hScale;
        var vScale;
        var matrix;
        var translateX;
        var translateY;

        //calculate the initial size
        pzImageClientSizeW = groupImageZoomPz.width();
        pzImageClientSizeH = groupImageZoomPz.height();
        pzHolderSizeW = groupImageZoomPzHolder.width();
        pzHolderSizeH = groupImageZoomPzHolder.height();
        hScale = (pzHolderSizeW / pzImageClientSizeW);
        vScale = hScale;
        //moved into position based on initial size
        matrix = 'matrix(' + hScale + ', 0, 0, ' + vScale + ', 0, 0)';
        groupImageZoomPz.css('transform', matrix);

        //calculate translate based on new size
        pzImageClientSizeW = groupImageZoomPz.width();
        pzImageClientSizeH = groupImageZoomPz.height();
        pzHolderSizeW = groupImageZoomPzHolder.width();
        pzHolderSizeH = groupImageZoomPzHolder.height();
        translateX = (pzHolderSizeW - pzImageClientSizeW) / 2;
        translateY = (pzHolderSizeH - pzImageClientSizeH) / 2;
        //move into position based on new size
        matrix = 'matrix(' + hScale + ', 0, 0, ' + vScale + ', ' + translateX + ', ' + translateY + ')';
        groupImageZoomPz.css('transform', matrix);
    }

    PanZoom("img.group-image-zoom-pz");

    var token;
    token = $('.group-image').attr('token');


    $('a.group-image-zoom').on('click', function () {
        groupImageZoom();
    });

    $('a.group-image-zoom-click').on('click', function () {
        groupImageZoomClick();
    });

    var pzImageReset = false;

    $('a.group-image-zoom-pz').on('click', function () {
        groupImageZoomPz();
    });

    $('a.group-image-zoom-pz-reset').on('click', function () {
        resetPzImage();
    });

    function groupImageZoom() {
        var zoomLevel = $(this).data('zoom-level');
        $('.group-image-holder')
            .removeClass('col-1 col-2 col-3 col-4 col-5 col-6 col-7 col-8 col-9 col-10 col-11 col-12')
            .addClass('col-' + zoomLevel)
            .removeClass('mr-auto')
            .removeClass('d-none')
            .addClass('ml-auto mr-auto');

        $('.group-image-zoom-instructions').addClass('d-none');
        $('.group-image-zoom-pz-instructions').addClass('d-none');
        $('.group-image-zoom-holder').addClass('d-none');
        $('.click-box').addClass('d-none');
        $('.group-image-zoom-pz-holder').addClass('d-none');
    }

    function groupImageZoomClick() {
        $('.group-image-holder')
            .removeClass('col-1 col-2 col-3 col-4 col-5 col-6 col-7 col-8 col-9 col-10 col-11 col-12')
            .addClass('col-6')
            .removeClass('ml-auto mr-auto')
            .addClass('mr-auto')
            .removeClass('d-none');
        $('.group-image-zoom-instructions').removeClass('d-none');
        $('.group-image-zoom-pz-instructions').addClass('d-none');
        $('.group-image-zoom-holder').removeClass('d-none');
        $('.click-box').removeClass('d-none');
        $('.group-image-zoom-pz-holder').addClass('d-none');
    }

    function groupImageZoomPz() {
        $('.group-image-zoom-instructions').addClass('d-none');
        $('.group-image-zoom-pz-instructions').removeClass('d-none');
        $('.group-image-zoom-holder').addClass('d-none');
        $('.group-image-holder').addClass('d-none');
        $('.click-box').addClass('d-none');
        $('.group-image-zoom-pz-holder').removeClass('d-none');

        if (pzImageReset === false) {
            resetPzImage();
            pzImageReset = true;
        }
    }


    $('.group-image').on("click", function (event) {

        var windowsScrollLeft = $(window).scrollLeft();
        var windowsScrollTop = $(window).scrollTop();
    
        var imgPosViewportX = $(this).offset().left - windowsScrollLeft;
        var imgPosViewportY = $(this).offset().top - windowsScrollTop;
    
        var mousePosViewportX = event.pageX - windowsScrollLeft;
        var mousePosViewportY = event.pageY - windowsScrollTop;
    
        var mousePosInsideImgX = mousePosViewportX - imgPosViewportX;
        var mousePosInsideImgY = mousePosViewportY - imgPosViewportY;
    
        var imgClientSizeW = this.clientWidth;
        var imgClientSizeH = this.clientHeight;
    
        var imgNativeSizeW = $(this).data('native-width');
        var imgNativeSizeH = $(this).data('native-height');
        var artifactNameCrypt = $(this).data('artifact-name');
    
        var mousePosPercentX = mousePosInsideImgX / imgClientSizeW;
        var mousePosPercentY = mousePosInsideImgY / imgClientSizeH;
    
        // Set the size and position of the click-box
        var boxWidth = Math.floor((imgClientSizeW / imgNativeSizeW) * imgClientSizeW);
        var boxHeight = Math.floor((imgClientSizeH / imgNativeSizeH) * imgClientSizeH);
        var boxPosX = Math.floor(mousePosInsideImgX - (boxWidth / 2));
        var boxPosY = Math.floor(mousePosInsideImgY - (boxHeight / 2));
    
        var zoomedImageUrl = base_url + "/franchise/zoom?imgClientSizeW=" + encodeURIComponent(imgClientSizeW) +
        "&imgClientSizeH=" + encodeURIComponent(imgClientSizeH) +
        "&mousePosPercentX=" + encodeURIComponent(mousePosPercentX) +
        "&mousePosPercentY=" + encodeURIComponent(mousePosPercentY)+
        "&artifactNameCrypt=" + artifactNameCrypt;

        $('.click-box').css({
            'top': boxPosY + 'px',
            'left': boxPosX + 'px',
            'width': boxWidth + 'px',
            'height': boxHeight + 'px'
        });
    
        // Update the src of the zoomed image
        $('#group-image-zoom-placeholder').attr('src', zoomedImageUrl);
    });
    



/****************************************************************************************** Tags Input - Groups *****************************************************************************************************************/


    var isaddedrow = false;
    var suppressChangeEvent = false;

    function fetchAndUpdateSubjectNames() {
        // Fetch updated subject names from the input field
        var subjectNamesString = $('#allSubjectNames').val();
        if(subjectNamesString){
           var subjectNames = JSON.parse(subjectNamesString); 
        }
        
        // Update all tagsinput fields with the new subject names
        $('input[data-role="tagsinput"]').each(function() {
            var $input = $(this);
            var selector = '#' + $input.attr('id');
    
            // Destroy any existing tagsinput and typeahead instances
            if ($input.data('tagsinput')) {
                $input.tagsinput('destroy');
            }
    
            // Reinitialize the tagsinput with the updated Typeahead configuration
            initializeTagsInput(selector, subjectNames);

        });
    }

    function initializeTagsInput(selector, subjectNames) {
        // Create a Bloodhound instance for typeahead suggestions
        var subjectNamesSuggestions = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: subjectNames
        });
    
        // Re-initialize the input field with updated typeahead and tagsinput
        $(selector).tagsinput({
            trimValue: true,
            allowDuplicates: false,
            typeaheadjs: [{
                hint: true,
                highlight: true,
                minLength: 1
            }, {
                name: 'subjectNames',
                source: subjectNamesSuggestions.ttAdapter()
            }]
        });
    
        setTimeout(() => {
            addEditButtonToTagsInput();
            makeTagsSortable(selector);  // Add the sortable feature after initialization
        }, 5);
    
        $(selector).on('itemAdded', function(event) {
            var $input = $(this);
            var newTag = event.item;
    
            // Prevent duplicates
            if ($input.tagsinput('items').filter(item => item === newTag).length > 1) {
                $input.tagsinput('remove', newTag);
                return;
            }
    
            setTimeout(() => {
                addEditButtonToNewTag(newTag);
                makeTagsSortable(selector);  // Ensure sortable is re-applied when new tags are added
            }, 5);

            if (isaddedrow) {
                createJsonData();
            }
        });

        // Add listener for itemRemoved events
        $(selector).on('itemRemoved', function(event) {
            // console.log(selector);
            debouncedCreateJsonData();
        });
    }

    let sources = [];
    let count = 0;

    // function makeTagsSortable(selector) {
    //     const $tagsInputContainer = $(selector).closest('.tagsSection').find('.bootstrap-tagsinput');
    
    //     $tagsInputContainer.sortable({
    //         items: 'span.tag',
    //         connectWith: '.tagsSection .bootstrap-tagsinput',
    //         placeholder: 'sortable-placeholder',
    //         helper: 'clone',
    //         start: function (event, ui) {
    //         },

    //         update: function(event, ui) {
    //             const sourceInput = $(ui.sender).closest('.tagsSection').find('input[data-role="tagsinput"]');
    //             const targetInput = $(this).closest('.tagsSection').find('input[data-role="tagsinput"]');
    //             const tagName = $(ui.item).text().trim();
    //             const isSameContainer = sourceInput.is(targetInput);    
    //             let targetInputId = targetInput.attr('id');  
    //             let sourceInputId = sourceInput.attr('id');
    
    //             // If dragged to a new container, remove from source
    //             if (!isSameContainer) {
    //                 sourceInput.tagsinput('remove', tagName, { silent: true });
    //             }
    
    //             // Get the target index for the drop position
    //             const targetIndex = ui.item.index();
    //             const targetTags = targetInput.tagsinput('items');

    //             // // Avoid duplicates: remove from target if already exists
    //             const existingIndex = targetTags.indexOf(tagName);
    //             if (existingIndex > -1) {
    //                 targetTags.splice(existingIndex, 1);
    //             }
    
    //             // // Insert the tag at the new position
    //             targetTags.splice(targetIndex, 0, tagName);
    //             sources.push(sourceInputId);
    //             if(count === 1){
    //                 document.getElementById(targetInputId).value = targetTags.toString();
    //             }
    //             count += 1;
    //         },
    //         over: function(event, ui) {
    //             var targetField = $(this); // The bootstrap-tagsinput container
    //             // Insert tag before the input field inside the targetField
    //             if(targetField.find('span.ui-sortable-handle').length === 0){
    //                 targetField.find('span.sortable-placeholder').insertBefore(targetField.find('span.twitter-typeahead'));
    //             }
    //         },
    //         stop: function(event, ui) {
    //             count = 0;
    //             createJsonData();
    //         }              
    //     }).disableSelection();
    // }  
     
    function makeTagsSortable(selector) {
        const $tagsInputContainer = $(selector)
            .closest('.tagsSection')
            .find('.bootstrap-tagsinput');
    
        $tagsInputContainer.sortable({
            items: 'span.tag',
            connectWith: '.tagsSection .bootstrap-tagsinput',
            placeholder: 'sortable-placeholder',
            helper: 'clone',
            start: function (event, ui) {},
    
            update: function (event, ui) {
                const sourceInput = $(ui.sender).closest('.tagsSection').find('input[data-role="tagsinput"]');
                const targetInput = $(this).closest('.tagsSection').find('input[data-role="tagsinput"]');
                const tagName = $(ui.item).clone().children().remove().end().text().trim();
                const isSameContainer = sourceInput.is(targetInput);
    
                // If dragged to a new container, remove from source
                if (!isSameContainer && sourceInput.length) {
                    sourceInput.tagsinput('remove', tagName, { silent: true });
                }
    
                // Rebuild tags in the target container based on new DOM order
                const newTags = [];
                $(this)
                    .find('span.tag')
                    .each(function () {
                        const cleanText = $(this).clone().children().remove().end().text().trim();
                        if (cleanText) newTags.push(cleanText);
                    });
    
                // Clear and re-add tags in the correct order
                targetInput.tagsinput('removeAll');
                newTags.forEach(tag => targetInput.tagsinput('add', tag));
    
                // Update hidden input value for form consistency
                document.getElementById(targetInput.attr('id')).value = newTags.join(',');
    
                // Trigger createJsonData() safely after update
                if (typeof createJsonData === 'function') {
                    setTimeout(() => createJsonData(), 50);
                }
            },
    
            over: function (event, ui) {
                const targetField = $(this);
                if (targetField.find('span.ui-sortable-handle').length === 0) {
                    targetField.find('span.sortable-placeholder')
                        .insertBefore(targetField.find('span.twitter-typeahead'));
                }
            },
    
            stop: function (event, ui) {
                count = 0;
            }
        }).disableSelection();
    }    
    
    fetchAndUpdateSubjectNames();

    function addEditButtonToNewTag(newTag) {
        var isPortrait = $('#isPortrait').val();    
        var editIcon = $('<i class="fa fa-edit ml-2 clickable" data-toggle="modal"' + 
        (isPortrait == 1 ? ' data-target="#GridSpellingEdits_Modal"' : ' data-target=""') + '></i>');

        $('.tagsSection').each(function () {
            var $tagsInput = $(this).find('.bootstrap-tagsinput');

            // Check if the new tag is in this tags input
            var $newTag = $tagsInput.find(`span.tag:contains(${newTag})`);

            if ($newTag.length) {
                $newTag.each(function () {
                    var subjectName = $(this).text().trim();
                    if ($(this).find('.fa-edit').length === 0) { // Check if the edit icon is already added
                        var editIconTmp = editIcon.clone().attr('data-subject-name', subjectName);
                        $(this).find('span[data-role="remove"]').before(editIconTmp);
                    }
                });
            }
        });
    }

    function addEditButtonToTagsInput() {
        var isPortrait = $('#isPortrait').val();    
        var editIcon = $('<i class="fa fa-edit ml-2 clickable" data-toggle="modal"' + 
        (isPortrait == 1 ? ' data-target="#GridSpellingEdits_Modal"' : ' data-target=""') + '></i>');

        $('.tagsSection').each(function () {
            $(this).find('.bootstrap-tagsinput span.tag').each(function () {
                // Check if the edit icon is already added
                if ($(this).find('.fa-edit').length === 0) {
                    var subjectName = $(this).text().trim();
                    var editIconTmp = editIcon.clone().attr('data-subject-name', subjectName);
                    $(this).find('span[data-role="remove"]').before(editIconTmp);
                }
            });
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }    

    // Debounced version of createJsonData with a delay of 300ms
    const debouncedCreateJsonData = debounce(createJsonData, 300);

    $('input[data-role="tagsinput"]').on('itemAdded', function(event) {
        debouncedCreateJsonData();
    });

    $('input[data-role="tagsinput"]').on('itemRemoved', function(event) {
        debouncedCreateJsonData();
    });

    $(document).on('click', '.remove_row', function(e) {
        e.preventDefault();

        let frontRow = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().includes('Front Row');
        });
        let middleRow = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().includes('Middle Row');
        });
        let backRow = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().includes('Back Row');
        });

        let currentRow = $(this).closest('.form-group.row-label');
        let prevRow = currentRow.prev('.form-group.row-label');
        var rowCount = parseInt(document.querySelector('input[name="groupCount"]').value);

        currentRow.remove();
        // console.log('removed');
        debouncedCreateJsonData();

        let maxRowNumber = $('[data-row-number]').length;

        // If the current row label is 'Front Row', rename the previous row's label to 'Front Row'
        if (currentRow.find('label').text().includes('Front Row') && prevRow.length) {
            if(rowCount === maxRowNumber){
                prevRow.find('label').html('Front Row');
            }else{
                if (backRow.length && frontRow.length && !middleRow.length) {
                    prevRow.find('label').html('Back Row');
                }else{
                    prevRow.find('label').html('Front Row <a href="#" class="remove_row">(Remove Row)</a>');
                }
            }
        }
        isaddedrow = true;
    });


    $('.add_row_button').on('click', function(e) {
        e.preventDefault();

        let frontRowOnly = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().trim() === 'Front Row';
        });
        let frontRow = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().includes('Front Row');
        });
        let middleRow = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().includes('Middle Row');
        });
        let backRow = $('.form-group.row-label').filter(function() {
            return $(this).find('label').text().includes('Back Row');
        });
        var rowCount = parseInt(document.querySelector('input[name="groupCount"]').value);


        if (frontRow.length && backRow.length && middleRow.length && !frontRowOnly.length) {
            insertMiddleRowBefore(rowCount);
        } else if (frontRow.length && backRow.length && !frontRowOnly.length) {
            // insertFrontRowBefore(frontRow, rowCount, 'Front_remove');
            insertMiddleRowBefore(rowCount);
        }else if (backRow.length && frontRowOnly.length) {
            insertFrontRowBefore(frontRowOnly, rowCount, 'Front');
        }else if (backRow.length) {
            insertFrontRowBeforeOnlyBack(backRow, rowCount);
        }
        isaddedrow = true;
        //createJsonData();
    });

    function insertFrontRowBeforeOnlyBack(backRow, rowCount) {
        backRow.find('label').html('Back Row');
        let newRowNumber = rowCount + 1;
        let maxRowNumber = $('[data-row-number]').length + 1;
    
        // Determine if the remove link should be shown
        let removeRowLink = '';

        if (rowCount < maxRowNumber) {
            removeRowLink = '<a href="#" class="remove_row">(Remove Row)</a>';
        }
    
        let newRowHtml = `
            <div class="form-group row-label tagsSection" data-row-number="${newRowNumber}">
                <label for="tags_${newRowNumber}">Front Row ${removeRowLink}</label>
                <input type="text" class="form-control tagsinput" id="tags_${newRowNumber}" name="tags[]" data-role="tagsinput" data-key="" value="" placeholder="Add a Name" autocomplete="off" style="display: none;">
            </div>
        `;
    
        $(`.form-group.row-label[data-row-number=${rowCount}]`).before(newRowHtml);
        fetchAndUpdateSubjectNames();
    }

    function insertFrontRowBefore(frontRow, rowCount, rowLabel) {
        if(rowLabel === 'Front_remove'){
            frontRow.find('label').html('Middle Row <a href="#" class="remove_row">(Remove Row)</a>');
        }else if(rowLabel === 'Front'){
            frontRow.find('label').html('Middle Row');
        }
        
        let maxRowNumber = 0;
        $('.form-group.row-label').each(function() {
            let rowNumber = parseInt($(this).attr('data-row-number'));
            if (rowNumber > maxRowNumber) {
                maxRowNumber = rowNumber;
            }
        });

        let newRowNumber = maxRowNumber + 1;

        // Determine if the remove link should be shown
        let removeRowLink = '';

        if (rowCount <= maxRowNumber) {
            removeRowLink = '<a href="#" class="remove_row">(Remove Row)</a>';
        }
    
        let newRowHtml = `
            <div class="form-group row-label tagsSection" data-row-number="${newRowNumber}">
                <label for="tags_${newRowNumber}">Front Row ${removeRowLink}</label>
                <input type="text" class="form-control tagsinput" id="tags_${newRowNumber}" name="tags[]" data-role="tagsinput" data-key="" value="" placeholder="Add a Name" autocomplete="off" style="display: none;">
            </div>
        `;
    
        $(`.form-group.row-label[data-row-number=${rowCount}]`).before(newRowHtml);
        fetchAndUpdateSubjectNames();
    }

    function insertMiddleRowBefore(rowCount) {
        let maxRowNumber = 0;
        $('.form-group.row-label').each(function() {
            let rowNumber = parseInt($(this).attr('data-row-number'));
            if (rowNumber > maxRowNumber) {
                maxRowNumber = rowNumber;
            }
        });

        let newRowNumber = maxRowNumber + 1;

        // Determine if the remove link should be shown
        let removeRowLink = '';

        if (rowCount < maxRowNumber) {
            removeRowLink = '<a href="#" class="remove_row">(Remove Row)</a>';
        }

        let newRowHtml = `
            <div class="form-group row-label tagsSection" data-row-number="${newRowNumber}">
                <label for="tags_${newRowNumber}">Middle Row <a href="#" class="remove_row">(Remove Row)</a></label>
                <input type="text" class="form-control tagsinput" id="tags_${newRowNumber}" name="tags[]" data-role="tagsinput" data-key="" value="" placeholder="Add a Name" autocomplete="off" style="display: none;">
            </div>
        `;

        $(`.form-group.row-label[data-row-number=${maxRowNumber}]`).before(newRowHtml);
        fetchAndUpdateSubjectNames();
    }

    function createJsonData() {
        let jsonData = {};
        var folderHash = $('#folderHash').val();
        var jobHash = $('#jobHash').val();

        // Sort the tagsSection elements so that the one with 'Absent List' label comes first
        let sortedSections = $('.tagsSection').sort(function(a, b) {
            let labelA = $(a).find('label').text().trim();
            let labelB = $(b).find('label').text().trim();

            // Place 'Absent List' first, others can follow in their original order
            if (labelA === 'Absent List') return -1;
            if (labelB === 'Absent List') return 1;
            return 0;
        });

        // Loop through each sorted tagsSection
        sortedSections.each(function(index) {
            let rowLabel = $(this).find('label').text().trim();
            if (rowLabel === 'Absent List') {
                rowLabel = 'Absent';
            } else {
                rowLabel = 'Row_' + (index-1);
            }

            let tagNames = $(this).find('input[data-role="tagsinput"]').val().split(',');

            if (rowLabel in jsonData) {
                jsonData[rowLabel] = jsonData[rowLabel].concat(tagNames);
            } else {
                jsonData[rowLabel] = tagNames;
            }
        });

        // Clean up empty or duplicate entries
        for (let label in jsonData) {
            jsonData[label] = jsonData[label].filter(name => name.trim() !== ""); // Remove empty names
            jsonData[label] = [...new Set(jsonData[label])]; // Remove duplicates
        }

        var subjectNamesjson = $('#allSubjectNames').data('key');

        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        if(JSON.stringify(subjectNamesjson) !== JSON.stringify(jsonData)){
            var targetUrl = base_url+"/franchise/proofing-change-log/group-change/submit";
            jsonData['folderHash'] = folderHash;
            jsonData['jobHash'] = jobHash;
            $.ajax({
                dataType: 'json',
                type: "POST",
                url: targetUrl,
                async: true,
                data: JSON.stringify(jsonData),
                cache: false,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Include CSRF token in the request headers
                },
                timeout: 60000,
                success: function (response) {
                    //  console.log(response);
                },
                error: function (e) {
                    // console.log('An error occurred:', e);
                }
            })
        }
        
    }

    /****************************************************************************************** GridSpellingModal - Groups *****************************************************************************************************************/

    let currentPage = 1;
    let loading = false;
    let currentSearch = "";
    
    // Load subjects via AJAX
    function loadGridSubjects(searchQuery = "") {
        if (loading) return;
        loading = true;
    
        // Reset tbody if first page
        if (currentPage === 1) {
            $("#spelling-subjects-tbody").empty();
        }
    
        $.get(window.GridConfig.subjectsGridUrl, {
            job: window.GridConfig.encryptedJob,
            folder: window.GridConfig.encryptedFolder,
            page: currentPage,
            search: searchQuery
        }, function(res) {
            // Append the returned rows
            $("#spelling-subjects-tbody").append(res.html);
    
            // Show or hide "Load More" button
            if (res.hasMore) {
                $("#loadMoreSubjects").removeClass("d-none");
            } else {
                $("#loadMoreSubjects").addClass("d-none");
            }
    
            // Lazy load images
            initLazyImages(document.getElementById("GridSpellingEdits_Modal"));
    
            loading = false;
    
            // If a search is active, highlight / filter rows
            if (searchQuery) {
                const val = searchQuery.toLowerCase();
                $("#spelling-subjects-tbody tr").each(function () {
                    const name = $(this).data("subject-name");
                    $(this).toggle(name.includes(val));
                });
            }
        });
    }
    
    // Lazy load images
    function initLazyImages(container) {
        const lazyImages = container.querySelectorAll("img.lazyloadgrid");
    
        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.onload = () => img.classList.add("loaded");
                        obs.unobserve(img);
                    }
                });
            }, { rootMargin: "300px 0px", threshold: 0.1 });
    
            lazyImages.forEach(img => observer.observe(img));
        } else {
            lazyImages.forEach(img => {
                img.src = img.dataset.src;
                img.onload = () => img.classList.add("loaded");
            });
        }
    }
    
    // Open modal and apply server-side search
    $('#GridSpellingEdits_Modal').on('show.bs.modal', function (event) {
        const modal = $(this);
        const button = $(event.relatedTarget); // icon clicked
        const subjectName = button.data('subject-name') || '';
    
        const filterBox = modal.find('input#subject-name-filter');
    
        // Set filter input
        filterBox.val(subjectName);
    
        // Reset page
        currentPage = 1;
        currentSearch = subjectName;
        $("#spelling-subjects-tbody").empty();
    
        // Load first page with server-side search
        loadGridSubjects(currentSearch);
    
        // Bind input for dynamic search
        filterBox.off('input').on('input', function () {
            currentPage = 1;
            currentSearch = $(this).val().trim();
            loadGridSubjects(currentSearch);
        });
    });
    
    // Load more button
    $("#loadMoreSubjects").on("click", function () {
        currentPage++;
        loadGridSubjects(currentSearch);
    });
    
    $('#subject-name-filter').on('input', function () {
        currentPage = 1;
        currentSearch = $(this).val().trim();
        // console.log(currentSearch);
        loadGridSubjects(currentSearch);
    });
    
    $(document).on("change", "input[id*='-grid-spelling-']", function (event) {
        var inputField = $(this);

        var skHash = inputField.data('skhash');
        var skEncrypted = inputField.data('skencrypted');
        var folderkeyEncrypted = inputField.attr('data-folderkeyencrypted');
        var originalValue = inputField.data('original-value');
        var oldValue = inputField.data('old-value');
        var newValue = inputField.val();
        //store values for roll back
        inputField.attr('data-old-value', newValue);

        //fire the AJAX change
        if (!suppressChangeEvent) {
            gridSpellingUpdate(skHash, skEncrypted, folderkeyEncrypted);
        }

        //show the revert button
        $("#" + skHash + "-grid-spelling-revert-button").removeClass('d-none')

        var tableRow = $(this).parent().parent();
       //console.log(tableRow);

        var newTableRowDataFilterValue = '';
        tableRow.find('input').each(function () {
            newTableRowDataFilterValue += $(this).val() + " ";
        });
        tableRow.attr('data-subject-name', newTableRowDataFilterValue.toLowerCase());
    });

    $(document).on("click", "span[id*='-grid-spelling-revert-button']", function (event) {
        var revertButton = $(this);

        var skHash = revertButton.data('skhash');
        var skEncrypted = revertButton.data('skencrypted');
        var folderkeyEncrypted = revertButton.attr('data-folderkeyencrypted');

        var inputGridSpellingSalutation = $("#" + skHash + "-grid-spelling-salutation");
        var inputGridSpellingFirstName = $("#" + skHash + "-grid-spelling-first-name");
        var inputGridSpellingLastName = $("#" + skHash + "-grid-spelling-last-name");
        var inputGridSpellingTitle = $("#" + skHash + "-grid-spelling-title");
        var inputGridSpellingPrefix = $("#" + skHash + "-grid-spelling-prefix");
        var inputGridSpellingSuffix = $("#" + skHash + "-grid-spelling-suffix");

        var originalValueSalutation = inputGridSpellingSalutation.data('original-value');
        var originalValueFirstName = inputGridSpellingFirstName.data('original-value');
        var originalValueLastName = inputGridSpellingLastName.data('original-value');
        var originalValueTitle = inputGridSpellingTitle.data('original-value');
        var originalValuePrefix = inputGridSpellingPrefix.data('original-value');
        var originalValueSuffix = inputGridSpellingSuffix.data('original-value');

        //roll back the values
        inputGridSpellingSalutation.val(originalValueSalutation);
        inputGridSpellingFirstName.val(originalValueFirstName);
        inputGridSpellingLastName.val(originalValueLastName);
        inputGridSpellingTitle.val(originalValueTitle);
        inputGridSpellingPrefix.val(originalValuePrefix);
        inputGridSpellingSuffix.val(originalValueSuffix);

        //fire the AJAX change
        gridSpellingUpdate(skHash, skEncrypted, folderkeyEncrypted);

        //hide the revert button
        $("#" + skHash + "-grid-spelling-revert-button").addClass('d-none')
    });


    function gridSpellingUpdate(skHash, skEncrypted, folderkeyEncrypted) {
        var targetUrl = base_url+"/franchise/proofing-change-log/subject-change/submit";

        var inputGridSpellingSalutation = $("#" + skHash + "-grid-spelling-salutation");
        var inputGridSpellingFirstName = $("#" + skHash + "-grid-spelling-first-name");
        var inputGridSpellingLastName = $("#" + skHash + "-grid-spelling-last-name");
        var inputGridSpellingTitle = $("#" + skHash + "-grid-spelling-title");
        var inputGridSpellingPrefix = $("#" + skHash + "-grid-spelling-prefix");
        var inputGridSpellingSuffix = $("#" + skHash + "-grid-spelling-suffix");

        if(inputGridSpellingSalutation.val() || inputGridSpellingFirstName.val() || inputGridSpellingLastName.val() || inputGridSpellingTitle.val() || inputGridSpellingPrefix.val() || inputGridSpellingSuffix.val()){
            var formData = new FormData();
            formData.append("issue", 'grid-spelling');
            formData.append("subject_key_hash", skHash);
            formData.append("subject_key_encrypted", skEncrypted);
            formData.append("folder_key_encrypted", folderkeyEncrypted);

            formData.append("new_salutation", inputGridSpellingSalutation.val());
            formData.append("new_first_name", inputGridSpellingFirstName.val());
            formData.append("new_last_name", inputGridSpellingLastName.val());
            formData.append("new_title", inputGridSpellingTitle.val());
            formData.append("new_prefix", inputGridSpellingPrefix.val());
            formData.append("new_suffix", inputGridSpellingSuffix.val());
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                dataType: 'json',
                type: "POST",
                url: targetUrl,
                async: true,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                timeout: 60000,

                success: function (response) {
                    var responseData = response.responseData;
                    // console.log(responseData);
                    var fullNamePortraitOld = responseData['fullNameOldPortrait'];
                    var fullNamePortraitNew = responseData['fullNamePortrait'];
                    var fullNameGroupOld = responseData['fullNameOldGroup'];
                    var fullNameGroupNew = responseData['fullNameGroup'];

                    var first_name_old = responseData['oldfirst_name'];
                    var first_name = responseData['first_name'];

                    var last_name_old = responseData['oldlast_name'];
                    var last_name = responseData['last_name'];

                    var title_old = responseData['title_old'];
                    var title = responseData['title'];

                    var salutation_old = responseData['salutation_old'];
                    var salutation = responseData['salutation'];

                    var prefix_old = responseData['prefix_old'];
                    var prefix = responseData['prefix'];

                    var suffix_old = responseData['suffix_old'];
                    var suffix = responseData['suffix'];

                    var useSalutationPortrait = responseData['useSalutationPortrait'];

                    var usePrefixSuffixPortrait = responseData['usePrefixSuffixPortrait'];

                    //find and replace names wrapped in specific HTML classes
                    $("." + skHash + "-first-name").text(first_name);
                    $("." + skHash + "-last-name").text(last_name);
                    $("." + skHash + "-title").text(title);
                    $("." + skHash + "-salutation").text(salutation);
                    $("." + skHash + "-prefix").text(prefix);
                    $("." + skHash + "-suffix").text(suffix); 
                    $("." + skHash + "-salutation").addClass('d-none');
                    $("." + skHash + "-prefix").addClass('d-none');
                    $("." + skHash + "-suffix").addClass('d-none');  

                    if(useSalutationPortrait)
                    {
                        $("." + skHash + "-salutation").removeClass('d-none');  
                    }

                    if(usePrefixSuffixPortrait)
                    {
                        $("." + skHash + "-prefix").removeClass('d-none');
                        $("." + skHash + "-suffix").removeClass('d-none');
                    }

                    //find and replace names in specific HTML input fields
                    $("." + skHash + "-form-spelling-first-name").val(first_name);
                    $("." + skHash + "-form-spelling-last-name").val(last_name);
                    $("." + skHash + "-form-spelling-title").val(title);
                    $("." + skHash + "-form-spelling-salutation").val(salutation);
                    $("." + skHash + "-form-spelling-prefix").val(prefix);
                    $("." + skHash + "-form-spelling-suffix").val(suffix);

                    $("." + skHash + "-grid-spelling-first-name").val(first_name);
                    $("." + skHash + "-grid-spelling-last-name").val(last_name);
                    $("." + skHash + "-grid-spelling-title").val(title);
                    $("." + skHash + "-grid-spelling-salutation").val(salutation);
                    $("." + skHash + "-grid-spelling-prefix").val(prefix);
                    $("." + skHash + "-grid-spelling-suffix").val(suffix);

                    $('tr.person-row.'+ skHash).attr('data-subject-name', fullNameGroupNew.toLowerCase());

                    //find and replace general
                    $('.' + skHash + '-find-replace').contents().filter(function () {
                        return this.nodeType === 3;
                    }).replaceWith(function () {
                        return this.nodeValue.replace(fullNamePortraitOld, fullNamePortraitNew);
                        createJsonData();
                    });

                    //update the JS array with the new name.
                    findSpanByText(fullNameGroupOld, fullNameGroupNew);
                    fetchAndUpdateSubjectNames();
                    // createJsonData();

                    $('#grid-spelling-acknowledge') 
                        .removeClass("text-success")
                        .removeClass("text-info")
                        .removeClass("text-warning")
                        .removeClass("text-danger")
                        .addClass("text-" + responseData['htmlUpdates']['alert'])
                        .html(responseData['htmlUpdates']['acknowledge']).fadeIn(500).delay(3000).fadeOut(500);

                    //show the history button
                    $('#' + skHash + '_history_edits_button').removeClass("d-none").addClass("d-inline-block");
                    if (responseData['resolved_status_id'] === 0) {
                        $('#' + skHash + '_history_edits_button').find("i.fa-history").addClass('text-success');
                    } else {
                        $('#' + skHash + '_history_edits_button').find("i.fa-history").addClass('text-danger');
                    }

                    if($('#allSubjectNames').val().includes(fullNameGroupOld)){                    
                        var inputValue = $('#allSubjectNames').val();
                        var updatedValue = inputValue.replace(fullNameGroupOld, fullNameGroupNew);
                        $('#allSubjectNames').val(updatedValue).trigger('change');
                    }
                },
                error: function (e) {
                    //alert("An error occurred: " + e.responseText.message);
                    //console.log(e);
                }
            })
        }

    }

    // var filterByText;
    // var filterByTextOriginal;
    // var foundPeople;
    // var foundCount;

    // $('#subject-name-filter').on('keyup change paste click', function () {
    //     filterByTextOriginal = $(this).val();
    //     filterByText = filterByTextOriginal.toLowerCase().replace("'", "\\'");

    //     if (filterByText.length >= 1) {
    //         $(".person-row").addClass("d-none");
    //         foundPeople = $("[data-subject-name*='" + filterByText + "']").removeClass("d-none");
    //         foundCount = foundPeople.length;
    //         $("#subject-name-filter-feedback").text("Found " + foundCount + " People containing the text '" + filterByTextOriginal + "'.");
    //     } else {
    //         $(".person-row").removeClass("d-none");
    //         //$(".subject").removeClass("d-none");
    //         $("#subject-name-filter-feedback").text("");
    //     }
    // });


    var linkHide = $(".people-photos-hide");
    var linkShow = $(".people-photos-show");
    var picWrapper = $(".person-pic-wrapper");

    linkHide.on('click', function () {
        linkHide.addClass("d-none");
        linkHide.removeClass("d-inline");

        linkShow.addClass("d-inline");
        linkShow.removeClass("d-none");

        picWrapper.addClass("d-none");
        picWrapper.removeClass("d-inline");
    });

    linkShow.on('click', function () {
        linkHide.addClass("d-inline");
        linkHide.removeClass("d-none");

        linkShow.addClass("d-none");
        linkShow.removeClass("d-inline");

        picWrapper.addClass("d-inline");
        picWrapper.removeClass("d-none");
    });

    /****************************************************************************************** GridSpellingModal - Groups *****************************************************************************************************************/

    function findSpanByText(fullNameOld, fullNameNew) {
        // Get all <span> elements on the page with the old text
        const spans = document.querySelectorAll('span');
        const matchingSpans = Array.from(spans).filter(span => span.textContent.trim() === fullNameOld);
    
        if (matchingSpans.length > 0) {
            matchingSpans.forEach(span => {
                // Find the closest .form-group div
                const formGroupDiv = span.closest('.form-group');
                if (formGroupDiv) {
                    // Find the input field within this form-group
                    const inputField = formGroupDiv.querySelector('input[type="text"][data-role="tagsinput"]');
                    if (inputField) {
                        // Replace the old text with the new text in the input field's value
                        const oldValue = inputField.value;
                        const newValue = oldValue.replace(new RegExp(fullNameOld, 'g'), fullNameNew); // Use global regex to replace all occurrences
                        inputField.value = newValue;
                    } else {
                        //console.log('Input field not found.');
                    }
                }
                // Update the span's inner HTML with the new text and edit icon
                var isPortrait = $('#isPortrait').val(); 
                span.innerHTML = fullNameNew + '<i class="fa fa-edit ml-2 clickable" data-toggle="modal"' + 
                (isPortrait == 1 ? ' data-target="#GridSpellingEdits_Modal"' : ' data-target=""') + ' data-subject-name="' + fullNameNew + '"></i><span data-role="remove"></span>';
            });
        } else {
            //console.log('No spans with the old text found');
        }
    }

    /****************************************************************************************** Proofing - Final submit *****************************************************************************************************************/

    $('#submitProofingDisabled').click(function () {
        submitProofDisabledAlert();
    });

    $('#saveProofing').click(function () {
        saveProofAlert();
    });

    $('#completeProofing').click(function () {
        completeProofAlert();
    });


    $('input:radio[name="submit-proof"]').change(function () {
        if ($(this).val() === 'save-for-later') {
            $('#saveProofing').removeClass("d-none");
            $('#completeProofing').addClass("d-none");
            $('#submitProofingDisabled').addClass("d-none");
        }else if($(this).val() === 'mark-as-complete'){
            $('#saveProofing').addClass("d-none");
            $('#completeProofing').removeClass("d-none");
            $('#submitProofingDisabled').addClass("d-none");
        }
    });

    function submitProofDisabledAlert() {
        var popMsg = "Please select Save or Complete option.";
        alert(popMsg);
    }

    function saveProofAlert() {
        var r = true;
        if (r === true) {
            //submit
            var formData = new FormData();
            formData.append("folderHash", $('#folderHash').val());
            formData.append("submitProof", 'save-for-later');
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            $.ajax({
                dataType: 'json',
                type: "POST",
                url: base_url+"/franchise/proofing-change-log/submit",
                async: true,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                timeout: 60000,
                success: function(response) {
                    if(response.status==true){window.location.href = response.url; }
                    else{$(".showinfo").html(response.alert);}
                },
                error: function (xhr, status) {
                    var obj = request.responseJSON.errors ;
                   // console.log(obj);
                }
            })

        } else {
            //no submit
        }
    }

    function completeProofAlert() {
        var popMsg = "Please ensure you take care when accepting your proofs. It is important to check for typographical, spelling and grammatical errors.\n\nYour acceptance of this proof provides your authority to proceed. MSP Photography cannot accept responsibility for the costs of re-prints once your approval has been granted.\n\nPlease contact MSP Photography for any clarification.";
        var r = confirm(popMsg);
        if (r === true) {
            //submit
            var formData = new FormData();
            formData.append("folderHash", $('#folderHash').val());
            formData.append("submitProof", 'mark-as-complete');
            formData.append("_token", $('meta[name="csrf-token"]').attr('content'));
            $.ajax({
                dataType: 'json',
                type: "POST",
                url: base_url+"/franchise/proofing-change-log/submit",
                async: true,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                timeout: 60000,
                success: function(response) {
                    if(response.status==true){window.location.href = response.url; }
                    else{$(".showinfo").html(response.alert);}
                },
                error: function (xhr, status) {
                    var obj = request.responseJSON.errors ;
                   // console.log(obj);
                }
            })

        } else {
            //no submit
        }
    }

    /****************************************************************************************** Proofing - Final submit *****************************************************************************************************************/

        //Modal Subject Proofing
        $('.modal_start').on('change', '.is_subject_select', function() {
            var modalId = $(this).closest('.modal_start').attr('id');
            var skHash = $(this).data('id');
            var selectedOption = $(this).val();
            var selectedText = $(this).find('option:selected').text();
            var classNameCurrent = document.querySelector('input[name="old_folder_name"]').value;
            var classNameNew = document.querySelector('input[name="folder_name"]').value;
    
            var inputFieldsContainer = $('#inputFieldsContainer_' + modalId.split('_')[0]);
            inputFieldsContainer.empty(); // Clear previous input fields
    
    
            var templateId;
            if (selectedText.includes('Job Title') || selectedText.includes('Salutation')) {
                // Get the template based on the selected option
                templateId = 'inputFieldTemplate_' + modalId.split('_')[0];
            } else {
                templateId = 'inputFieldTemplate' + selectedOption + '_' +skHash;
            }

            var template = $('#' + templateId).html();
    
            if (template) {
                var firstName  = $('#'+skHash+'-first-name').text().trim();
                var lastName   = $('#'+skHash+'-last-name').text().trim();
                var salutation = $('#'+skHash+'-salutation').text().trim();
                var title      = $('#'+skHash+'-title').text().trim();
                var picture    = $('#'+skHash+'_picture').text().trim();
                var folder     = $('#'+skHash+'_folder').text().trim();
                var prefix     = $('#'+skHash+'-prefix').text().trim();
                var suffix     = $('#'+skHash+'-suffix').text().trim();
                
                var templateElement = $(template);
                
                var firstNameInput  = templateElement.find('input[name="' + skHash + '_new_first_name"]');
                var lastNameInput   = templateElement.find('input[name="' + skHash + '_new_last_name"]');
                var salutationInput = templateElement.find('input[name="' + skHash + '_new_salutation"]');
                var prefixInput     = templateElement.find('input[name="' + skHash + '_new_prefix"]');
                var suffixInput     = templateElement.find('input[name="' + skHash + '_new_suffix"]');
                var titleInput      = templateElement.find('input[name="' + skHash + '_new_title"]');
                var pictureInput    = templateElement.find('input[name="' + skHash + '_picture_issue"]');
                var folderInput     = templateElement.find('select[name="' + skHash + '_folder_issue"]');
                
                /* ✅ SET ONLY IF VALUE EXISTS */
                if (firstName && firstNameInput.length) {
                    firstNameInput.val(firstName);
                }
                if (lastName && lastNameInput.length) {
                    lastNameInput.val(lastName);
                }
                if (salutation && salutationInput.length) {
                    salutationInput.val(salutation);
                }
                if (prefix && prefixInput.length) {
                    prefixInput.val(prefix);
                }
                if (suffix && suffixInput.length) {
                    suffixInput.val(suffix);
                }
                if (title && titleInput.length) {
                    titleInput.val(title);
                }
                if (picture && pictureInput.length) {
                    pictureInput.val(picture);
                }
                if (folder && folderInput.length) {
                    folderInput.val(folder);
                }
                
                inputFieldsContainer.append(templateElement);
            }
    
            $('.homedfolders option').each(function() {
                if ($(this).text() === classNameCurrent) {
                    $(this).text(classNameNew); // Correct way to update the text
                }
            });
        });
    
        //subject submit
        $('.subject_submit').click(function(){
            suppressChangeEvent = true;
            $('#subjects_questions option[value=""]').removeAttr('selected');
            var skHash = $(this).attr('id').replace('_issue_submit','');
            var data = $('#'+skHash+"_form").serialize();

            // Remove the prefix from the serialized data
            data = data.replace(new RegExp(skHash + "_", "g"), "");
            $.ajax({
                dataType: 'json',
                type: 'POST',
                url: base_url+"/franchise/proofing-change-log/subject-change/submit",
                data: data,
                success: function(response) {
                    if (response && response.responseData) {
                        var responseData = response.responseData;
                        // console.log(responseData);
                        var fullNamePortraitOld = responseData['fullNameOldPortrait'];
                        var fullNamePortraitNew = responseData['fullNamePortrait'];
                        var fullNameGroupOld = responseData['fullNameOldGroup'];
                        var fullNameGroupNew = responseData['fullNameGroup'];
                        var useSalutationPortrait = responseData['useSalutationPortrait'];
                        var usePrefixSuffixPortrait = responseData['usePrefixSuffixPortrait'];

                        $('#spelling_'+ skHash).addClass('d-none');
                        $('#picture_'+ skHash).addClass('d-none');
                        $('#folder_'+ skHash).addClass('d-none');
                        $('#title-salutation_'+ skHash).addClass('d-none');
                        $('#prefix-suffix_'+ skHash).addClass('d-none');
    
                        $('#'+ skHash +'_acknowledge').removeClass("text-success text-info text-warning text-danger");
                        $('#'+ skHash +'_acknowledge').addClass("text-" + responseData.htmlUpdates.alert);
                        $('#'+ skHash +'_acknowledge').html(responseData.htmlUpdates.acknowledge).fadeIn(500).delay(4000).fadeOut(1000);
    
                        //find and replace names wrapped in specific HTML classes
                        $('.'+ skHash +'-first-name').text(responseData.first_name);
                        $('.'+ skHash +'-last-name').text(responseData.last_name);
                        $('.'+ skHash +'-title').text(responseData.title);
                        $('#'+ skHash +'_picture').text(responseData.picture);
                        $('#'+ skHash +'_folder').text(responseData.folder);
                        $("." + skHash + "-salutation").text(responseData.salutation);
                        $("." + skHash + "-prefix").text(responseData.prefix);
                        $("." + skHash + "-suffix").text(responseData.suffix); 
                        $("." + skHash + "-salutation").addClass('d-none');
                        $("." + skHash + "-prefix").addClass('d-none');
                        $("." + skHash + "-suffix").addClass('d-none');  

                        if(useSalutationPortrait)
                        {
                            $("." + skHash + "-salutation").removeClass('d-none');  
                        }

                        if(usePrefixSuffixPortrait)
                        {
                            $("." + skHash + "-prefix").removeClass('d-none');
                            $("." + skHash + "-suffix").removeClass('d-none');
                        }
                        $("." + skHash + "-form-spelling-first-name").val(responseData.first_name);
                        $("." + skHash + "-form-spelling-last-name").val(responseData.last_name);
                        $("." + skHash + "-form-spelling-title").val(responseData.title);
                        $("." + skHash + "-form-spelling-salutation").val(responseData.salutation);
                        $("." + skHash + "-form-spelling-prefix").val(responseData.prefix);
                        $("." + skHash + "-form-spelling-suffix").val(responseData.suffix);
                        
                        $('.'+ skHash +'-grid-spelling-first-name').val(responseData.first_name);
                        $('.'+ skHash +'-grid-spelling-last-name').val(responseData.last_name);
                        $('.'+ skHash +'-grid-spelling-title').val(responseData.title);
                        $('.'+ skHash +'-grid-spelling-salutation').val(responseData.salutation);
                        $('.'+ skHash +'-grid-spelling-prefix').val(responseData.prefix);
                        $('.'+ skHash +'-grid-spelling-suffix').val(responseData.suffix);

                        $("#" + skHash + "-grid-spelling-revert-button").removeClass('d-none')

                        $('tr.person-row.'+ skHash).attr('data-subject-name', fullNameGroupNew.toLowerCase());
    
                        // Define or select the modal element
                        var modal = $('#' + skHash + '_modal'); // Adjust the selector to target the correct modal
                        if (modal.length > 0) {
                            modal.find("#history-box-subject-history-table").html(responseData.htmlUpdates.full_name);
                        }
                        
                        //find and replace general
                        $('.'+ skHash +'-find-replace').contents().filter(function () {
                            return this.nodeType === 3;
                        }).replaceWith(function () {
                            return this.nodeValue.replace(fullNamePortraitOld, fullNamePortraitNew);
                            createJsonData();
                        });
                        
                        findSpanByText(fullNameGroupOld, fullNameGroupNew);
                        fetchAndUpdateSubjectNames();
                        // createJsonData();

                        $('#subjects_questions option[value=""]').attr('selected', 'selected');
    
                        //show the history button
                        $('#'+ skHash +'_history_edits_button').removeClass("d-none").addClass("d-inline-block");
    
                        if (responseData.resolved_status_id === 0) {
                            $('#'+ skHash +'_history_edits_button').find("i.fa-history").addClass('text-success');
                        } else {
                            $('#'+ skHash +'_history_edits_button').find("i.fa-history").addClass('text-danger');
                        }
                 
                        if ($('#allSubjectNames').val().includes(fullNameGroupOld)) {
                            var inputValue = $('#allSubjectNames').val();
                            var updatedValue = inputValue.replace(fullNameGroupOld, fullNameGroupNew);
                            $('#allSubjectNames').val(updatedValue).trigger('change');
                        }
                       // console.log("Completed");
                    } else {
                      // console.log("Error: Invalid response structure.");
                    }
                    suppressChangeEvent = false;
                },
                error: function (e) {
                    //alert("An error occurred: " + e.responseText.message);
                    console.log(e);
                }
            });
        });
    
        //Modal HistoryEdits 
        $('#HistoryEdits_Modal').on('show.bs.modal', function (event) {
            var modal = $(this);
    
            var button = $(event.relatedTarget); // Button that triggered the modal
            var skHash = button.data('skhash'); // Extract info from data-* attributes
            var titleHtml = $("#" + skHash + "_history_edits_name_populate").html();
            var spinningHtml = '<i class="fa fa-spinner fa-3x fa-spin" aria-hidden="true"></i>';
    
            modal.find("#history-box-subject-name").html(titleHtml);
            modal.find("#history-box-subject-history-table").html(spinningHtml)
    
            selectedRoute = $("#" + skHash + "_history_edits_button").data('route');
    
            var targetUrl = selectedRoute;
            $.ajax({
                dataType: 'html',
                type: 'GET',
                url: targetUrl,
                success: function (htmlResult, textStatus, jqXHR) {
                    //console.log(htmlResult);
                    modal.find("#history-box-subject-history-table").html(htmlResult)
                },
                error: function (xhr, status) {
                    //console.log("Sorry, there was a problem!");
                },
                complete: function (xhr, status) {
                    //console.log("Completed");
                }
            });
        });

});

    


    //Folder Proofing Validation message
    function toggleValidationMessage(id) {
        var selectElement = document.getElementById('folder_question_' + id);
        if(selectElement){
            var noMessageElement = document.getElementById('folder_question_' + id + '_no');
            if (selectElement.value == '0') {
                noMessageElement.style.display = 'block';
            } else {
                noMessageElement.style.display = 'none';
            }
            checkProceedStatus(); //proceed button disable
        }
    }

    function checkProceedStatus() {
        var proceedSelects = document.querySelectorAll('.is_proceed_select');
        var showDisabledNext = false;

        proceedSelects.forEach(function(select) {
            var isProceedValue = select.dataset.isProceed;
            var selectValue = select.value;

            if (isProceedValue == '1' && selectValue != '1') {
                // If a required select is not set to 'Yes', disable the "Next" button
                showDisabledNext = true;
                return; // No need to check further
            }
            
            if (selectValue === '') {
                // If any question is not answered, disable the "Next" button
                showDisabledNext = true;
                return; // No need to check further
            }
        });

        var nextButton = document.getElementById('subjectNext');
        var nextDisabledButton = document.getElementById('subjectNextDisabled');

        if (showDisabledNext) {
            nextButton.classList.add("d-none");
            nextDisabledButton.classList.remove("d-none");
        } else {
            nextButton.classList.remove("d-none");
            nextDisabledButton.classList.add("d-none");
        }
    }


    //Global Functions

    // Store the initial value of each select element with class 'is_proceed_select'
    $('.is_proceed_select').each(function() {
        $(this).data('previousValue', $(this).val());
    });

    function isVisible(element) {
        var el = $(element);
        return el.is(':visible') && el.css('visibility') !== 'hidden' && el.css('display') !== 'none';
    }

    $('#classNext').click(function(){       
            // Check if the hidden input with id='recorded_subjectmissing' has any value
            var recordedSubjectMissingValue = $('#recorded_subjectmissing').val();
            if (recordedSubjectMissingValue) {
                document.getElementById("subject_missing_names").value= recordedSubjectMissingValue;
            }

            // Check if the hidden input with id='recorded_pageissue' has any value
            var recordedPageIssueValue = $('#recorded_pageissue').val();
            if (recordedPageIssueValue) {
                document.getElementById("subject_general_issue_text").value= recordedPageIssueValue;
            }
    });

    function sendFolderChanges(location, issue, classNameNew, note) { 
        var returnResponse;

        var formData = new FormData();
        formData.append("issue", issue);
        formData.append("note", note);
        formData.append("newValue", classNameNew);

        formData.append("_token", $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            dataType: 'json',
            type: "POST",
            url: location,
            async: true,
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            timeout: 60000,

            success: function (response) {
                returnResponse = response;
            },
            error: function (e) {
                //alert("An error occurred: " + e.responseText.message);
            //console.log(e);
                returnResponse = false;
            },
            complete: function (xhr) {
                // if (xhr.status >= 400 && xhr.status <= 499) {
                //     window.location.replace("<?= $targetUrlStatus4xx ?>");
                // }
            }
        })
        return returnResponse;
    }


