/**
 * Grade book management
 * @module local_customgrader/grader
 * @author Camilo José Cruz rivera
 * @copyright 2018 Camilo José Cruz Rivera <cruz.camilo@correounivalle.edu.co>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'jquery',
        'local_customgrader/wizard_categories',
        'local_customgrader/bootstrap',
        'local_customgrader/sweetalert',
        'local_customgrader/jqueryui'],
    function (
        $,
        wizard,
        bootstrap,
        sweetalert,
        jqueryui) {
        var CATEGORY_LEVEL_CLASS_PREFIX = 'catlevel';
        var GRADE_ITEM_HEADERS_SELECTOR = '.item';
        var GRADE_ITEM_HEADERS_SELECTOR_WITH_CATEGORY_ITEMS = '.item,.categoryitem';
        var GRADE_CATEGORY_HEADERS_SELECTOR = '.category';
        /**
         * @method update_grade_items
         * @desc uptade the items which needsupdate from a course
         * @param {integer} course_id
         * @return {boolean}
         */
        function update_grade_items(course_id) {

            var curso = course_id;
            var data = { type: 'update_grade_items', course: curso };

            $.ajax({
                type: "POST",
                data: data,
                url: "managers/ajax_processing.php",
                async: false,
                success: function (msg) {
                    if (msg == '1') {
                        console.log('update ok');
                    } else {
                        console.log('update fail');
                    }
                },
                dataType: "text",
                cache: "false",
                error: function (msg) { console.log(msg); },
            });
        }

        /**
         * When a cell is deleted, the column headers show info incorrectly.
         *
         * Supose the next table
         *+-------------+-------------+
         | header1(c0) | header2(c1) |
         +-------------+-------------+
         | c2   | c3   | c4   | c5   |
         +------+------+------+------+
         * when c3 is deleted, the table is converted to the next incorrect table
         * +-------------+-------------+
         | header1(c0) | header2(c2) |
         +-------------+-------------+
         | c2   | c4   | (empty)     |
         +------+------+-------------+
         * you can see than c4 is not child of c3 at start, but for incorrect behaviour
         * this is incorrect by default, this method fix this updating the colspan
         * of headers for obtain the next result when c3 is deleted
         * +-------------+--------------+
         | header1(c0) | header2(c1)  |
         +-------------+--------------+
         | c2          | c4 | (empty) |
         +-------------+----+---------+
         *
         * @see {@link https://www.w3.org/WAI/tutorials/tables/irregular/}
         * @param tr_header_selector Selector for the tr element than contains
         *  the list of elements where the element th is deleted
         * @param cell_deleted_class Class of cell deleted in format c{index},
         *  examples: if cell deleted is the cell number 8, c8 is given at cell_deleted_class
         */
        function fix_categories_headers_after_item_delete(item_id) {
            var parent_category_id = get_id_of_parent_category_of_an_item(item_id);
            var parent_category = $(`.category[data-categoryid="${parent_category_id}"]`);
            var parent_category_colspan = parent_category.prop('colspan');
            parent_category.prop('colspan', Number(parent_category_colspan -1) );
        }

        function fix_categories_headers_after_category_child_delete(category_id) {
            var parent_category_id = get_id_of_parent_category_of_an_category(category_id);
            var parent_category = $(`.category[data-categoryid="${parent_category_id}"]`);
            var parent_category_colspan = parent_category.prop('colspan');
            parent_category.prop('colspan', Number(parent_category_colspan -1) );
        }

        /**
         * Return the parent category id by a given item based in the html table
         * @param item_id
         * @returns {*}
         */
        function get_id_of_parent_category_of_an_item(item_id) {
            var item_dom_th = get_dom_th_item(item_id);
            console.log(item_dom_th, 'item_dom_th');
            return item_dom_th.data('parent-categoryid');
        }

        /**
         * Return the parent category id by a given category id based in the html table
         * @param category_id
         * @returns {*}
         */
        function get_id_of_parent_category_of_an_category(category_id) {
            var category_dom_th = get_dom_th_category(category_id);
            return category_dom_th.data('parent-categoryid');
        }
        function get_dom_categories() {
            return  $('.category');
        }
        function deduce_number_of_categories() {
            return get_dom_categories().length;
        }
        function onlyUnique(value, index, self) {
            return self.indexOf(value) === index;
        }
        /**
         * Return the classes of the categories th elements in array
         * example: [category, cell, categorylevel1, category, cell, categorylevel2...]
         * @param unique return the class name distinct
         * @see onlyUnique
         * @returns {Array}
         */
        function get_dom_categories_classes(unique) {

            var categories = get_dom_categories();
            var categories_classes = [];
            categories.each((index, item) => {

                for(var entry of item.classList.values() ) {
                    categories_classes.push(entry);
                }
            });

            if(unique) {
                return categories_classes.filter(onlyUnique);
            } else {
                return categories_classes;
            }

        }
        function get_dom_th_item(item_id) {
            return $(`${GRADE_ITEM_HEADERS_SELECTOR}[data-itemid="${item_id}"]`);
        }
        function get_dom_th_category(category_id) {
            return $(`${GRADE_CATEGORY_HEADERS_SELECTOR}[data-categoryid="${category_id}"]`);
        }
        /**
         * Return the names of the category levels
         * example: [catlevel1, catlevel2, ...]
         */
        function get_category_levels_names(unique) {
        var categories_classes = get_dom_categories_classes(true);

        var category_class_for_level =  categories_classes.filter(category_class => {
                return category_class.includes(CATEGORY_LEVEL_CLASS_PREFIX);
            });

        if ( unique ){
            return category_class_for_level.filter( onlyUnique );
        } else {
            return category_class_for_level;
        }
        }

        function deduce_number_of_categories_levels() {
            var category_level_names = get_category_levels_names(false);

            return category_level_names.length;
        }
        function delete_item_dom_elements(item_id) {
            var targets = $(`*[data-itemid="${item_id}"]`);
            targets.hide('slow', function(){ targets.remove(); });
            fix_categories_headers_after_item_delete(item_id);
        }
        function get_category_child_items_ids(category_id) {
            console.log( $(`${GRADE_ITEM_HEADERS_SELECTOR_WITH_CATEGORY_ITEMS}[data-parent-categoryid="${category_id}"]`));
            var item_ids = [];
            $(`${GRADE_ITEM_HEADERS_SELECTOR_WITH_CATEGORY_ITEMS}[data-parent-categoryid="${category_id}"]`)
                .each(() => {
                        item_ids.push($(this).data('itemid'));
                    }
                );
            return item_ids;
        }
        function delete_category_dom_elements(category_id) {
            var dom_th_category = get_dom_th_category();
            dom_th_category.hide('slow', function() {dom_th_category.remove();});
            fix_categories_headers_after_category_child_delete(category_id);
        }
        function make_add_element_button(element_id, element_name, element_type) {
            return $('<a/>',
                {
                    "class": 'add-component',
                    "title": "Adicionar elemento"
                }
            ).html('+')
                .click(function() {
                    wizard.show_delete_element_dialog(
                        element_id,
                        element_type,
                        element_name,
                        wizard.getCourseId(),
                        () => delete_item_dom_elements(element_id));
                });
        }

        /**
         * Return a delete button for all elements (categories and items)
         * @param element_id
         * @param element_name
         * @param element_type
         * @returns {*|jQuery}
         */
        var make_delete_element_button = function (element_id, element_name, element_type) {
            return $('<a/>',
                {
                    "class": 'delete-component',
                    "title": "Eliminar"
                }
            ).html('x')
                .click(function() {
                    wizard.show_delete_element_dialog(
                        element_id,
                        element_type,
                        element_name,
                        wizard.getCourseId(),
                        () => delete_item_dom_elements(element_id));
                });
        };

        var make_delete_item_button = function (item_id, item_name) {
            return make_delete_element_button(item_id, item_name, wizard.ELEMENT_TYPES.ROW);
        };
        /**
         * Make a button for categories, to create child element
         * @param category_id
         * @param item_name
         */
        var make_add_category_child_element_button = function (category_id) {
            return $('<a/>',
                {
                    "class": 'add-component',
                    "title": 'Adicionar elemento'
                }
                ).html('+');
        };
        /**
         * @method validateNota
         * @desc Verifies if a grade is correct value (not empty or within a range)
         * @param {DOM element} selector represents all inputs where every grade is registered
         * @return {boolean} Return false in case there was any mistake or error, true if the grade is correct or there isn't a selected grade
         */
        function validateNota(selector) {
            var bool = false;
            var nota = selector.val();

            if (nota > 5 || nota < 0) {
                swal({
                    title: "Ingrese un valor valido, entre 0 y 5. \n\rUsted ingresó: " + nota,
                    html: true,
                    type: "warning",
                    confirmButtonColor: "#d51b23"
                });
                selector.val(grade);
                bool = false;
            } else if (nota == '' && grade != '') {
                selector.val('0');
                bool = true;
            } else if (nota == '' && grade == '' || nota == grade) {
                bool = false;
            } else {
                bool = true;
            }



            return bool;
        }

        /**
         * @method bloquearTotales
         * @desc disable some fields on front page and changes CSS (font weight and size)
         * @return {void}
         */
        function bloquearTotales() {

            $('.topleft').attr('colspan', '3');

            $('.cat').each(function () {
                var input = $(this).children().next('.text');
                input.attr('disabled', true);
                input.css('font-weight', 'bold');
            });

            $('.course').each(function () {
                var input = $(this).children().next('.text');
                input.attr('disabled', true);
                input.css('font-weight', 'bold');
                input.css('font-size', 16);
            });

        }

        ////////////////////////////////////////////////////////////////////////////////////////////
        ////SOLO RAMA UNIVALLE
        /**
         * @method marckAses
         * @desc Removes every student who's not 'pilo'. IF the student is 'pilo' remove href attribute (link to other page)
         * @param {array} ases 'ases' students to filtrate with the entry from DOM
         * @return {void}
         */
        function marckAses(ases) {
            $("#user-grades").children().children().each(function () {
                if ($(this).attr('data-uid') != undefined) {
                    if (isAses($(this).attr('data-uid'), ases)) {
                        $(this).attr('class' , 'ases');
                    }
                }
            });
        }


        /**
         * @method isAses
         * @desc verifies if a student id is in an array of 'ases'
         * @param {integer} id
         * @param {array} ases
         * @return {boolean} True if the student is 'ases', false otherwise
         */
        function isAses(id, ases) {
            for (var i = 0; i < ases.length; i++) {
                if (ases[i].split("_")[1] === id) {
                    return true;
                }
            }
            return false;
        }


        /**
         * @method getIDs
         * @desc Returns an array of ids, belonging to students 'ases'
         * @return {array} array of students id
         */
        function getIDs() {
            var ases = new Array;
            $("#students-ases").children().each(function () {
                ases.push($(this).attr("id"));
            });
            return ases;
        }
        ////////////////////////////////////////////////////////////////////////////////////////////


        /**
         * Return the item name based in the th for item element returned by jquery
         * @param element
         * @returns {*}
         */
        function get_item_name_from_table_header(element) {
            var item_link = element.find('.gradeitemheader');
            var item_name = '';
            if(item_link) {
                item_name = item_link.text();
            }
            return item_name;
        }
        /**
         * Return the category name based in the th for item element returned by jquery
         * @param element
         * @returns {*}
         */
        function get_category_name_from_table_header(element) {
            var category_span = element.find('.gradeitemheader');
            var category_name = '';
            if(category_span) {
                category_name = category_span.text();
            }
            return category_name;
        }
        /**
         * Return the item id based in the th element returned by jquery
         * @param element
         * @returns {*}
         */
        function get_item_id_from_table_header(element) {
            return element.data('itemid');
        }
        /**
         * Return the item id based in the th element returned by jquery
         * @param element
         * @returns {*}
         */
        function get_category_id_from_table_header(element) {
            return element.data('categoryid');
        }
        function make_item_mini_menu_options(item_id, item_name) {
            return $('<div/>',
                {
                    class: 'mini-menu-options'
                })
                .append(
                    make_delete_item_button(item_id, item_name)
                );
        }
        function make_category_mini_menu_options(category_id, category_name) {
            return $('<div/>',
                {
                    class: 'mini-menu-options'
                })
            .append(
                make_add_category_child_element_button(category_id),
                make_delete_element_button(category_id, category_name, wizard.ELEMENT_TYPES.CATEGORY)
            );
        }
        /**
         * Add a menu of options to items (delete)
         */
        function add_items_mini_menu_options() {
            $(GRADE_ITEM_HEADERS_SELECTOR).each(function (){
                var item_id = get_item_id_from_table_header($(this));
                var item_name = get_item_name_from_table_header($(this));
                $(this).append(make_item_mini_menu_options(item_id, item_name));
            });
        }

        /**
         * Add a menu of options to categories (delete, add child element)
         */
        function add_category_mini_menu_options() {
            $(GRADE_CATEGORY_HEADERS_SELECTOR).each(function() {
               var category_id = get_category_id_from_table_header($(this));
               var category_name = get_category_name_from_table_header($(this));

               $(this).append(make_category_mini_menu_options(category_id, category_name));
            });
        }

        var del_and_show_table = function() {
            let element = $("#user-grades").clone();
            element
                .attr('id', '#user-grades-inserted')
                .attr('display', 'none');

            $(".gradeparent").append(element);
          $("#user-grades").remove();
          element.attr('display', 'block');

          console.log('borrdo y creado');
        };

        return {
            init: function () {
                var grade;
                setTimeout(del_and_show_table, 5000);
                $(document).ready(function () {
                    add_items_mini_menu_options();
                    add_category_mini_menu_options();
                    console.log(get_category_child_items_ids(28215));
                    ////////////////////////////////////////////////////////////////////////////////////////////
                    ////SOLO RAMA UNIVALLE
                    var ases = getIDs();
                    marckAses(ases);
                    $('#wizard_button, #tutorial-button').click(function() {
                        $('.gradeparent').removeClass('sticky-table sticky-headers sticky-ltr-cells');
                        //$('table').removeClass('table table-striped');
                        $('tbody > tr ').each(function() {
                            if(!$(this)[0].hasAttribute('data-uid')) {
                                $(this).removeClass('sticky-row');
                            }
                        });
                        $('.heading').removeClass('sticky-row');
                        $('.heading:first-child').removeClass('sticky-cell');
                        $('.header.user.cell.c0').removeClass('sticky-cell');
                    });
                    $('#close-tutorial').click(function() {
                        $('.gradeparent').addClass('sticky-table sticky-headers sticky-ltr-cells');
                        $('table').addClass('table table-striped');
                        $('tbody > tr ').each(function() {
                            if(!$(this)[0].hasAttribute('data-uid')) {
                                $(this).addClass('sticky-row');
                            }
                        });
                        $('.heading').addClass('sticky-row');
                        $('.heading:first-child').addClass('sticky-cell');
                        $('.header.user.cell.c0').addClass('sticky-cell');
                    });
                    ////////////////////////////////////////////////////////////////////////////////////////////

                    bloquearTotales();
                    if ($('.gradingerror').length != 0) {
                        //if gradingerror, update items that needsupdate
                        swal({
                                title: "Recalculando ítems.",
                                text: "Debido al proceso de actualización de moodle se debe realizar este paso.\nGracias por su paciencia \nDe seguir presentando este problema, por favor dirigirse a la configuración de calificaciones de moodle",
                                type: "warning",
                                showCancelButton: false,
                                confirmButtonClass: "btn-danger",
                                confirmButtonText: "Continuar",
                                closeOnConfirm: false
                            },
                            function () {
                                update_grade_items(wizard.getCourseId());
                                location.reload();
                            });
                    }
                });



                $(document).on('blur', '.text', function () {
                    if (validateNota($(this))) {
                        var id = $(this).attr('id').split("_");
                        var userid = id[1];
                        var itemid = id[2];
                        var nota = $(this).val();
                        var curso = wizard.getCourseId();
                        var data = { user: userid, item: itemid, finalgrade: nota, course: curso };
                        $.ajax({
                            type: "POST",
                            data: data,
                            url: "managers/ajax_processing.php",
                            async: false,
                            success: function (msg) {

                                var updGrade = msg.nota;

                                if (updGrade == true) {
                                    console.log("Nota actualizada");
                                } else {
                                    swal('Error',
                                        'Error al actualizar la nota',
                                        'error');
                                }

                            },
                            dataType: "json",
                            cache: "false",
                            error: function (msg) {
                                console.log(msg);
                            }
                        });
                    }
                });

                $(document).on('focus', '.text', function () {
                    grade = $(this).val();
                    //console.log(grade);
                });

                $(document).on('keypress', '.text', function (e) {

                    var tecla = (document.all) ? e.keyCode : e.which;

                    //backspace to delete and (.)  always allows it
                    if (tecla == 8 || tecla == 46) {
                        return true;
                    }
                    // entry pattern, just accept numbers
                    var patron = /[0-9]/;
                    var tecla_final = String.fromCharCode(tecla);
                    return patron.test(tecla_final);
                });


                $(document).on('click', '.reload', function () {

                    location.reload();
                });


            }
        };
    });

