/**
* Wizard Categories management
* @module local_customgrader/wizard_categories
* @author Camilo José Cruz rivera
* @copyright 2018 Camilo José Cruz Rivera <cruz.camilo@correounivalle.edu.co> 
* @license  http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define([
    'jquery',
   /* 'local_customgrader/sweetalert',
    'local_customgrader/bootstrap',
    'local_customgrader/jqueryui'*/], function ($, __sweet_alert) {
    var CONFIRMATION_BUTTON_COLOR = "#d51b23";
    var ELEMENT_TYPES = {
        CATEGORY : 'cat',
        ROW: 'row',
    };
    var PREDEFINED_SWEET_ALERT_OPTIONS = {
        cancelButtonClass: 'swal-button-cancel',
        confirmButtonColor: CONFIRMATION_BUTTON_COLOR,
        confirmButtonClass: 'swal-button-confirm'
    };

    /**
     * @method getCourseId
     * @desc Returns the id of the course obtained from the url
     * @return {int} course_id
     */
    function getCourseId() {
        var informacionUrl = window.location.search.split("=");
        var curso = -1;
        for (var i = 0; i < informacionUrl.length; i += 2) {
            if (informacionUrl[i] === "?id") {
                curso = informacionUrl[i + 1];
            }
        }
        return curso;
    }

    var swal = function (options, callback) {

        var swal_options = {...options, ...PREDEFINED_SWEET_ALERT_OPTIONS };
        return __sweet_alert(
            swal_options,
            callback
        );
    };


    /**
     * @method load_parent_category
     * @desc Load parent categories options in a select
     * @param {int} id_course
     * @param {int} id_element
     * @param {char} type_e
     * @return {void}
     */
    function load_parent_category(id_course, id_element, type_e) {
        $.ajax({
            type: "POST",
            data: {
                course: id_course,
                element: id_element,
                type_e: type_e,
                type: "loadParentCat"
            },
            url: "managers/ajax_processing.php",
            success: function (msg) {
                console.log(msg);
                $('#padre').html(msg.html);
            },
            dataType: "json",
            cache: "false",
            error: function (msg) {
                console.log('ajax error');
                console.log(msg);
            },
        });

    }

    /**
     * @method deleteElement
     * @desc Delete an item or category
     * @param {int} id_e
     * @param {int} id_course
     * @param {char} type_e
     * @return {Promise}
     */
    var deleteElement = function (id_e, id_course, type_e) {
        return $.ajax({
            type: "POST",
            data: {
                type_ajax: "deleteElement",
                id: id_e,
                courseid: id_course,
                type: type_e
            },
            dataType: "json",
            cache: "false",
            url: "managers/ajax_processing.php"
        });
    };




    /**
     * @method createElement
     * @desc Validate info and using ajax create an element
     * @param {int} aggParent
     * @param {int} idParent
     * @return {void}
     */
    function createElement(aggParent, idParent) {

        var tipoItem = $("#tipoItem").val();
        var curso = getCourseId();

        if (tipoItem == 'CATEGORÍA') {
            if (validateDataCat(aggParent)) {
                var name = $.trim($('#inputNombre').val());
                var weigth = $('#inputValor').val();
                var agg = getAggregation($('#tipoCalificacion').prop('selectedIndex'));
                $.ajax({
                    type: "POST",
                    data: {
                        course: curso,
                        parent: idParent,
                        fullname: name,
                        agregation: agg,
                        tipo: tipoItem,
                        peso: weigth
                    },
                    url: "managers/ajax_processing.php",
                    success: function (msg) {
                        //se recibe el mensaje, si el ingreso fue exitoso entonces se recarga el combo de categorias padre
                        if (msg == 1) {

                            swal({
                                title: "Categoria añadida con exito",
                                html: true,
                                type: "success",
                                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                            });
                            loadCategories(curso);
                        }
                        else if (msg == 0) {
                            //si no fue exitosa la creacion se envia el mensaje de alerta
                            swal({
                                title: "Error al añadir la categoria (server error)",
                                html: true,
                                type: "error",
                                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                            });
                        }

                    },
                    cache: "false",
                    error: function (msg) {
                        swal({
                            title: "Error al intentar añadir la categoria",
                            html: true,
                            type: "error",
                            confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                        });
                        console.log(msg);
                    },
                });
            }
        }
        else if (tipoItem == 'ÍTEM') {
            if (validateDataIt(aggParent)) {
                var name = $.trim($('#inputNombre').val());
                var weigth = $('#inputValor').val();
                $.ajax({
                    type: "POST",
                    data: {
                        course: curso,
                        parent: idParent,
                        fullname: name,
                        tipo: tipoItem,
                        peso: weigth
                    },
                    url: "managers/ajax_processing.php",
                    success: function (msg) {
                        //se recibe el mensaje, si el ingreso fue exitoso entonces se recarga el combo de categorias padre
                        if (msg == 1) {
                            swal({
                                title: "item añadido con exito",
                                html: true,
                                type: "success",
                                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                            });
                            loadCategories(curso);
                        }
                        else if (msg == 0) {
                            swal({
                                title: "Error al añadir el item (server error)",
                                html: true,
                                type: "error",
                                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                            });
                        }

                    },
                    cache: "false",
                    error: function (msg) {
                        swal({
                            title: "Error al intentar añadir el item",
                            html: true,
                            type: "error",
                            confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                        });
                        console.log(msg);
                    },
                });
            }
        }
        else if (tipoItem == 'PARCIAL') {
            if (validateDataParcial(aggParent)) {
                var name = $.trim($('#inputNombre').val());
                var weigth = $('#inputValor').val();
                $.ajax({
                    type: "POST",
                    data: {
                        course: curso,
                        parent: idParent,
                        fullname: name,
                        agregation: 6,
                        tipo: tipoItem,
                        peso: weigth
                    },
                    url: "managers/ajax_processing.php",
                    success: function (msg) {
                        //se recibe el mensaje, si el ingreso fue exitoso entonces se recarga el combo de categorias padre
                        if (msg == 1) {

                            swal({
                                title: "Categoria añadida con exito",
                                html: true,
                                type: "success",
                                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                            });
                            loadCategories(curso);
                        }
                        else if (msg == 0) {
                            //si no fue exitosa la creacion se envia el mensaje de alerta
                            swal({
                                title: "Error al añadir la categoria (server error)",
                                html: true,
                                type: "error",
                                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                            });
                        }

                    },
                    cache: "false",
                    error: function (msg) {
                        swal({
                            title: "Error al intentar añadir la categoria",
                            html: true,
                            type: "error",
                            confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                        });
                        console.log(msg);
                    },
                });
            }
        }
        else {
            swal({
                title: "Seleccione el tipo de elemento que desea crear",
                html: true,
                type: "warning",
                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
            });
        }


    }

    function getAggregation(index) {
        switch (index) {
            case 1:
                return 0;
                break;
            case 2:
                return 10;
                break;
        }
    }

    /**
     * @method validateDataIt
     * @desc Validate information when creating an Item
     * @param {int} aggregation
     * @return {void}
     */
    function validateDataIt(aggregation) {
        var nombre = $.trim($('#inputNombre').val());
        if (nombre === '') {
            swal({
                title: "Ingrese el nombre del ítem que desea crear",
                html: true,
                type: "warning",
                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
            });
            return false;
        }
        if (aggregation === 10) {
            var peso = $('#inputValor').val();
            if (peso === '') {
                swal({
                    title: "Ingrese un peso válido entre 0 y " + maxweight,
                    html: true,
                    type: "warning",
                    confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                });
                return false;
            }
        }

        return true;
    }

    /**
     * @method validateDataParcial
     * @desc Validate information when creating parcial category
     * @param {int} aggregation
     * @return {void}
     */
    function validateDataParcial(aggregation) {
        var nombre = $.trim($('#inputNombre').val());
        if (nombre === '') {
            swal({
                title: "Ingrese el nombre del parcial que desea crear",
                html: true,
                type: "warning",
                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
            });
            return false;
        }
        if (aggregation === 10) {
            var peso = $('#inputValor').val();
            if (peso == '') {
                swal({
                    title: "Ingrese un peso válido entre 0 y " + maxweight,
                    html: true,
                    type: "warning",
                    confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                });
                return false;
            }
        }

        return true;
    }
    /**
     * @method loadCategories
     * @desc Load categories and items in the wizard
     * @param {int} id_course
     * @return {void}
     */
    function loadCategories(id) {
        $.ajax({
            type: "POST",
            data: {
                course: id,
                type: "loadCat"
            },
            url: "managers/ajax_processing.php",
            success: function (msg) {
                $("#mymodalbody").html(msg);
            },
            dataType: "text",
            cache: "false",
            error: function (msg) {
                console.log(msg);
            },
        });
    }
    /**
     * @method validateDataCat
     * @desc Validate information when creating category
     * @param {int} aggregation
     * @return {void}
     */
    function validateDataCat(aggregation) {
        if ($('#tipoCalificacion').prop('selectedIndex') == 0) {
            swal({
                title: "Seleccione el tipo de calificación de la categoría que desea crear",
                html: true,
                type: "warning",
                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
            });
            return false;
        }
        var nombre = $.trim($('#inputNombre').val());
        if (nombre == '') {
            swal({
                title: "Ingrese el nombre de la categoría que desea crear",
                html: true,
                type: "warning",
                confirmButtonColor: CONFIRMATION_BUTTON_COLOR
            });
            return false;
        }
        if (aggregation == 10) {
            var peso = $('#inputValor').val();
            if (peso == '') {
                swal({
                    title: "Ingrese un peso válido entre 0 y " + maxweight,
                    html: true,
                    type: "warning",
                    confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                });
                return false;
            }
        }
        else {

        }
        return true;
    }

    /**
     *
     * @param element_id
     * @param element_type
     * @param element_name
     * @param course_id
     * @param callback_on_success Optional callback to be executed on success delete element
     * @returns {*}
     */
    var show_delete_element_dialog = function (element_id, element_type, element_name, course_id, callback_on_success) {
        var tipo = 'No seleccionado';
        if (element_type === ELEMENT_TYPES.CATEGORY) {
             tipo = "la categoria: " + element_name;
        } else {
            tipo = "el item: " + element_name;
        }
        var titulo = "Esta seguro que desea eliminar " + tipo + "?";
        return swal({
                title: titulo,
                text: "Tenga en cuenta que NO SE PODRAN RECUPERAR ninguna de la notas que haya registrado en este elemento una vez borrado!",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "Aceptar, Borrar " + tipo,
                cancelButtonText: "Cancelar",
                closeOnCancel: true
            },
            (isConfirm) => {
                if (isConfirm) {
                    console.log('pos si0');
                    return deleteElement(element_id, course_id, element_type)
                        .then(response => {
                            swal({
                                title: "Listo",
                                text: response.msg,
                                type: "success",
                                showCancelButton: false,
                                showConfirmButton: false,
                                timer: 2400,
                            });

                            loadCategories(course_id);
                            if(callback_on_success) {
                                callback_on_success();
                            }
                        })
                        .catch(response=> {
                            console.log(response);
                        });
                } else {
                    swal({
                        title: "Cancelado",
                        type: "error",
                        showCancelButton: false,
                        showConfirmButton: false,
                        timer: 1200,
                    });
                    return false;
                }
            });
    };

    var get_add_element_form = function () {
        var newDiv = $("<div class = 'divForm' style= 'display:none'>");
        newDiv.load("templates/categories_form.html");
        newDiv.toggle('slow', 'swing');
        return newDiv;
    };

    return {
        deleteElement: deleteElement,
        getCourseId: getCourseId,
        get_add_element_form: get_add_element_form,
        show_delete_element_dialog: show_delete_element_dialog,
        ELEMENT_TYPES: ELEMENT_TYPES,
        init: function () {
            var old_weight;
            var maxweight;
 
            //Metodos de Wizard de crear categorias e items

            $(document).on('click', '#wizard_button', function () {
                $("#modalCategories").modal({
                    backdrop: false
                });
                $('.fondo').show();
            });

            $(document).on('click', '#tutorial-button', function (){
                $('#video-frame').attr('src', 'https://www.youtube.com/embed/8Bm_obAFBYo');
                $("#tutorialModal").modal({
                    backdrop: false
                });
                $('.fondo').show();
                
            });

            $(document).on('click', '#close-tutorial', function() {                
                $('.fondo').hide();
            });

            $('#tutorialModal').on('hidden.bs.modal', function (e) {
                $('#video-frame').attr('src', '');
            });

            /**
             * Event to close modal
             */
            $(document).on('click','#cancel', function () {
                $('.fondo').hide();
            });
            $(document).on('click', '.mymodal-close', function () {
                location.reload();
            });

            /**
             * Event to open edit modal and load all information
             */
            $(document).on('click', '.edit', function () {
                //se carga titulo (item o categoria)
                var titulo = $(this).attr('title');
                $("#titulo").text(titulo);

                var curso = false;

                $("#nombre_editar").show();
                $("#label_nombre").show();
                $('#alta').show();
                $('#div_padres').show();

                if ($(this).hasClass('curso')) {
                    curso = true;
                    $('#alta').hide();
                    $("#nombre_editar").hide();
                    $("#label_nombre").hide();
                    $('#div_padres').hide();
                }

                //se carga la informacion del elemento
                var id_course = getCourseId();
                var id_element = $(this).attr('id');
                $('#save_edit').attr('name', id_element);

                //se evalua si el elemento es item o categoria
                if (titulo === "Editar Categoría") {
                    $('#type_cal').show();
                    var tipo = $(this).parent().parent().attr('id');
                    $('#otro').hide();
                    if (tipo != 10 && tipo != 0 && tipo != 6) {
                        tipo = 99;
                        $('#otro').show();
                    }
                    $('#calific').prop('value', tipo);
                    var type =  ELEMENT_TYPES.CATEGORY;
                    var nombre = $(this).parent().parent().text();
                    if (nombre.search("-") == -1) {
                        var nom_text = nombre.split("(");
                        nombre = nom_text[0];
                        peso = nom_text[1];
                        if (!curso) {
                            $('#peso').show();
                        }
                        peso = peso.replace('(', '');
                        peso = peso.replace(')', '');
                        peso = peso.replace(' ', '');
                        peso = peso.replace('%', '');
                        old_weight = peso;
                        $('#peso_editar').val(peso);

                        var maxweight = $(this).attr('data-maxweight');
                        $('.maxweight-edit').attr('id', maxweight);
                    } else {
                        nombre = nombre.split("-")[0];
                        $('#peso').hide();
                    }
                } else {
                    //se oculta el tipo de calificacion
                    $('#type_cal').hide();
                    //se carga el peso de tenerlo
                    var peso = $(this).parent().parent().prev().text();
                    if (peso == '-') {
                        $('#peso').hide();
                        $('#peso_editar').val(peso);
                    } else {
                        $('#peso').show();
                        peso = peso.replace('(', '');
                        peso = peso.replace(')', '');
                        peso = peso.replace(' ', '');
                        peso = peso.replace('%', '');
                        old_weight = peso;
                        $('#peso_editar').val(peso);

                        var maxweight = $(this).parent().attr('id');
                        $('.maxweight-edit').attr('id', maxweight);
                    }
                    var type = 'it';
                    var nombre = $(this).parent().parent().prev().prev().attr('title');

                    //Se evalua si es una asignacion para no dejar modificar el nombre
                    if (nombre == 'Enlazar a la actividad Tarea') {
                        $("#nombre_editar").hide();
                        $("#label_nombre").hide();
                    }
                }
                //se carga el nombre
                $("#nombre_editar").val(nombre);

                //se cargan las categorias seleccionando la categoria padre del elemento
                load_parent_category(id_course, id_element, type);
                //se abre el modal
                $("#edit").modal({
                    backdrop: false
                });
                $('.fondo').show();

            });

            /**
             * Event to save changes on edit modal validating all data
             */
            $(document).on('click', '#save_edit', function () {
                var id_element = $(this).attr("name");
                var new_nombre = $("#nombre_editar").val();
                var new_peso = $('#peso_editar').val();
                var maxweight = parseFloat($('.maxweight-edit').attr('id')) + parseFloat(old_weight);
                var new_calif = $('#calific').val();
                var parent_id = $('#padre').val();

                if (new_peso == '-') {
                    new_peso = 0;
                }

                if (new_calif == '99') {
                    swal({
                        title: "Tipo de Calificacion no valida.",
                        text: "No es posible editar una categoria con un tipo de calificacion diferente a Promedio Simple, Promedio Ponderado, o Calificacion mas alta.\n Por favor seleccione uno de estos tipos de calificacion",
                        html: true,
                        type: "warning",
                        confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                    });
                    return;
                }


                if (new_peso > maxweight) {
                    swal({
                        title: "Peso no valido.",
                        text: "El peso ingresado supera el peso máximo posible de la categoria padre que es: " + maxweight + "% \n Para crear un nuevo elemento primero configure los pesos de los demas.",
                        html: true,
                        type: "warning",
                        confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                    });
                    return;
                }

                if (new_nombre == '') {
                    swal({
                        title: "Ingrese el nuevo nombre del elemento",
                        html: true,
                        type: "warning",
                        confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                    });
                    return;
                }

                var titulo = $('#titulo').text();
                if (titulo.split(' ')[1] == 'Ítem') {
                    var type_e = "it";
                    new_calif = false;
                } else {
                    type_e = ELEMENT_TYPES.CATEGORY;
                }

                var course_id = getCourseId();
                $.ajax({
                    type: "POST",
                    data: {
                        course: course_id,
                        element: id_element,
                        type_e: type_e,
                        newNombre: new_nombre,
                        newPeso: new_peso,
                        newCalific: new_calif,
                        type: "editElement",
                        parent: parent_id
                    },
                    url: "managers/ajax_processing.php",
                    success: function (msg) {
                        if (msg.error) {
                            swal('Error',
                                msg.error,
                                'error');
                        } else {
                            swal({
                                title: "Listo",
                                text: msg.msg,
                                type: "success",
                                showCancelButton: false,
                                showConfirmButton: false,
                                timer: 1300,
                            });
                            $("#edit").modal('hide');
                            $('.fondo').hide();
                            loadCategories(course_id);
                            console.log(msg);
                        }
                    },
                    dataType: "json",
                    cache: "false",
                    error: function (msg) {
                        swal('Server Error',
                                msg.error,
                                'error');
                        console.log("AJAXerror");
                        console.log(msg);
                    },
                });
            });

            /**
             * Event to confirm the deletion of an item or a category 
             */
            $(document).on('click', '.delete', function () {
                var element = $(this).parent().parent().parent().attr('id').split('_');
                var course_id = getCourseId();
                var element_name = $(this).attr("data-name");
                var element_id = element[1];
                var element_type= element[0];
                show_delete_element_dialog(element_id, element_type, element_name, course_id);

            });

            $(document).on("click", ".new", function () {
                maxweight = $(this).parent().parent().children().next('.maxweight').attr('id');
                if (maxweight <= 0) {
                    swal({
                        title: "No se pueden crear mas categorías o ítems en la categoria seleccionada.",
                        text: "\n\r El peso de los elementos dentro de ésta suma 100%.\n\r Para crear un nuevo elemento primero configure los pesos de los demas.",
                        html: true,
                        type: "warning",
                        confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                    });
                    return;
                }
                $('.new').prop('disabled', true);
                $('.delete').prop('disabled', true);
                $('.edit').prop('disabled', true);



                var parent = $(this).parent().parent();
                parent.append('<hr style = "margin: 6px 0;border-top: 1px solid #ddd">');
                var add_element_form = get_add_element_form();
                parent.append(add_element_form);

                window.setTimeout(function () {

                    var agg = parent.attr("id");

                    if (agg == 10) { //if is ponderated
                        $("#divPeso").show();
                        $("#inputValor").on('blur', function () {
                            //se revisa que el elemento digitado cumpla con las restricciones
                            var numero = $(this).val();
                            var maxPeso = parseInt($(this).parent().parent().parent().parent().children().next('.maxweight').attr('id'));
                            //si no cumple con la restriccion de estar entre 0 y 100 entonces se realiza el aviso y se pone el valor en 0
                            if (numero < 0 || numero > maxPeso) {
                                swal({
                                    title: "El valor debe estar entre 0 y el peso máximo: " + maxPeso,
                                    text: "\n\rUsted ingresó: " + numero,
                                    html: true,
                                    type: "warning",
                                    confirmButtonColor: CONFIRMATION_BUTTON_COLOR
                                });
                                $(this).val('');
                            }
                        });
                        $('#inputValor').on('keypress', function (e) {
                            var tecla = (document.all) ? e.keyCode : e.which;

                            //Tecla de retroceso para borrar y el punto(.) siempre la permite
                            if (tecla == 8 || tecla == 46) {
                                return true;
                            }
                            // Patron de entrada, en este caso solo acepta numeros
                            var patron = /[0-9]/;
                            var tecla_final = String.fromCharCode(tecla);
                            return patron.test(tecla_final);

                        });
                    }

                    $("#tipoItem").on('change', function () {
                        var index = $(this).prop('selectedIndex');
                        if (index == 1) {
                            $('#divTipeC').show();
                        }
                        else {
                            $('#divTipeC').hide();
                        }
                    });

                    $('#save').on('click', function () {
                        var agg = $(this).parent().parent().parent().parent().attr("id");
                        var parent = $(this).parent().parent().parent().parent().parent().attr("id");
                        parent = parent.split('_')[1];
                        createElement(agg, parent);
                    });

                    $('#cancel').on('click', function () {
                        var id = getCourseId();
                        loadCategories(id);
                    });
                }, 400);
            });




        }
    };
});

