define([
    'local_customgrader/grader-utils',
    'local_customgrader/grader-enums',
    'local_customgrader/grader-service',
    'local_customgrader/vendor-vue'
], function (utils, g_enums, g_service, Vue) {
    console.log(g_service);

    const columnFinalGrade = {text: "Nota final"};
    const columnStudentCode= {text: "Código estudiante"};
    const columnStudentFullName = {text: "Nombre de el estudiante"};


    var aggregations = [
        {
            id: g_enums.aggregations.SIMPLE,
            name: "Promedio Simple"
        },
        {
            id: g_enums.aggregations.PROMEDIO,
            name: "Promedio Ponderado"
        }
    ];
    var sortStudentsMethod = function (sortStudentMethodType) {
        switch (sortStudentMethodType) {
            case g_enums.sortStudentMethods.NAME:
                return  (studentA, studentB) => studentA.firstname[0] > studentB.firstname[0];
            case g_enums.sortStudentMethods.LAST_NAME:
                return  (studentA, studentB) => studentA.lastname > studentB.lastname;
        }
    };
    var mutationsType = {
        SET_STATE: 'setAllState',
        ADD_GRADE: 'addGrade',
        SET_GRADE: 'setGrade',
        SET_CATEGORY: 'setCategory',
        SET_LEVELS: 'setLevels',
        SET_ITEM: 'setItem',
        ADD_GRADE_TO_STUDENT: 'addGradeToStudent',
        DELETE_ITEM: 'deleteItem',
        DELETE_GRADE: 'deleteItemGrades',
        SET_SELECTED_CATEGORY_ID: 'setSelectedCategoryId'
    };
    var actionsType = {
        FETCH_STATE: 'fetchAllState',
        FILL_GRADES: 'fillGrades',
        UPDATE_GRADE: 'updateGrade',
        DELETE_ITEM: 'deleteItem',
        UPDATE_CATEGORY: 'setCategory',
        UPDATE_ITEM: 'setItem',
        DELETE_ITEM_GRADES: 'deleteItemGrades'
    };
    var store = {
        state : {
            aggregations: aggregations,
            additionalColumnsAtFirst: [
                columnStudentFullName,
                columnStudentCode
            ],
            additionalColumnsAtEnd: [
                columnFinalGrade
            ],
            students /*: Dict<studentId: Student> */: {},
            selectedCategoryId: null,
            items /*: Dict<itemId:Item> */: {},
            categories /*: Array<Category> */: [],
            grades /*: Dict<gradeId:Grade> */: {},
            levels: [], // First level is course level, last level is item level, between
            //this two levels are category levels
            course: {fullname: 'Nombre completo de el curso'},
        },

        mutations: {
            [mutationsType.DELETE_ITEM] (state, itemId) {
                Vue.delete(state.items, itemId);
            },
            [mutationsType.DELETE_GRADE] (state, gradeId) {
                Vue.delete(state.grades, gradeId);
            },
            [mutationsType.ADD_GRADE] (state, newGrade) {
                newGrade.id = ID();
                let student = state.students[newGrade.userid];
                let studentGradeIds = student.gradeIds? student.gradeIds: [];
                Vue.set(state.grades, newGrade.id, newGrade);
                Vue.set(state.students[student.id], 'gradeIds', [...studentGradeIds, newGrade.id]);
            },
            [mutationsType.ADD_GRADE_TO_STUDENT] (state, payload) {
                let grade = payload.grade;
                let studentId = payload.studentId;
                let student = state.students[studentId];
                let studentGradeIds = student.gradeIds? student.gradeIds: [];
                Vue.set(state.students[studentId], 'gradeIds', [...studentGradeIds, grade.id]);
            },
            [mutationsType.SET_ITEM] (state, newItem) {
                Vue.set(state.items, newItem.id, newItem);
            },
            [mutationsType.SET_LEVELS] (state, levels) {
                state.levels = levels;
            },
            [mutationsType.SET_CATEGORY] (state, newCategory) {
                let category_index = state.categories.map(category => category.id).indexOf(newCategory.id);
                Vue.set(state.categories, category_index, newCategory);
            },
            [mutationsType.SET_GRADE] (state, newGrade, oldGrade) {
                if( oldGrade ) {
                    if (oldGrade.id !== newGrade.id) {
                        Vue.delete(state.grades, oldGrade.id);
                        let studentGradeIds = state.students[student.id].gradeIds;
                        let newGradeIds =
                            [...studentGradeIds.filter(grade => grade.id !== oldGrade.id), newGrade.id]
                        Vue.set(state.students[student.id], 'gradeIds', newGradeIds);
                    }
                }
                Vue.set(state.grades, newGrade.id, newGrade);
            },
            [mutationsType.SET_SELECTED_CATEGORY_ID] (state, newSelectedId) {
                state.selectedCategoryId = newSelectedId;
            },
            [mutationsType.SET_STATE] (state, newState) {
                console.log(newState);
                state.levels = newState.levels;
                let studentsDict = {};
                newState.students.forEach(student => {
                    studentsDict[student.id] = student;
                });
                state.students = studentsDict;
                let itemsDict = {};
                newState.items.forEach(item => {
                    itemsDict[item.id] = item;
                });
                state.items = itemsDict;
                state.categories = newState.categories;
                let gradesDict = {};
                newState.grades.forEach(grade => {
                    gradesDict[grade.id] = {...grade, finalgrade: utils.removeInsignificantTrailZeros(grade.finalgrade)};
                });
                state.grades = gradesDict;
                state.course = newState.course;
            }
        },
        actions: {
            [actionsType.DELETE_ITEM] ({commit, dispatch, state}, itemId) {
                g_service.delete_item(itemId, state.course.id)
                    .then( response => {
                        commit(mutationsType.SET_LEVELS, response.levels);
                        commit(mutationsType.DELETE_ITEM, itemId);
                        dispatch(actionsType.DELETE_ITEM_GRADES, itemId);
                    });
            },
            [actionsType.DELETE_ITEM_GRADES]({commit, state}, itemId) {
                let gradeIds = Object.keys(state.grades);
                let gradeIdsToDelete = [];
                gradeIds.forEach(gradeId => {
                    if(state.grades[gradeId].itemid === itemId) {
                        gradeIdsToDelete.push(gradeId);
                    }
                });
                console.log(gradeIdsToDelete, 'grade ids to delete');
                gradeIdsToDelete.forEach(gradeId => {
                    commit(mutationsType.DELETE_GRADE, gradeId);
                });
            },
            [actionsType.FILL_GRADES] ({ commit, state, getters }) {
                let studentIds = Object.keys(state.students);
                let grades = Object.values(state.grades);
                studentIds.forEach(studentId => {
                    for(var item of getters.orderedItemSet /* The grades are printed in this order*/) {
                        let gradeResult = grades.find(grade => grade.userid === studentId && grade.itemid === item.id);
                        if(!gradeResult) {
                            let grade = {
                                userid: studentId,
                                itemid: item.id,
                                finalgrade: null,
                                rawgrademin: item.grademin,
                                rawgrademax: item.grademax
                            };
                            commit(mutationsType.ADD_GRADE, grade);
                        } else {
                            commit(mutationsType.ADD_GRADE_TO_STUDENT, {studentId: studentId, grade: gradeResult} );
                        }
                    }

                }) ;
            },
            [actionsType.UPDATE_GRADE] ({commit, state}, grade) {
                g_service.update_grade(grade, state.course.id)
                    .then( response => {
                        console.log(response);
                        commit(mutationsType.SET_GRADE, grade);
                    });
            },
            [actionsType.UPDATE_CATEGORY]({dispatch, commit}, category) {
                g_service.update_category(category)
                    .then( response => {
                        let category = response.category;
                        let levels = response.levels;
                        commit(mutationsType.SET_CATEGORY, category);
                        commit(mutationsType.SET_LEVELS, levels);
                    })
            },
            [actionsType.UPDATE_ITEM]({dispatch, commit}, item) {
                g_service.update_item(item)
                    .then( response => {
                        let item = response.item;
                        let levels = response.levels;
                        commit(mutationsType.SET_ITEM, item);
                        commit(mutationsType.SET_LEVELS, levels);
                    })
            },
            [actionsType.FETCH_STATE] ({dispatch, commit}) {
                g_service.get_grader_data(utils.getCourseId())
                    .then( response => {
                        commit(mutationsType.SET_STATE, response);
                        dispatch(actionsType.FILL_GRADES);
                    })
            }
        },
        getters: {
            courseLevel: (state) => {
                return state.levels[0]?state.levels[0][0]: [];
            },
            selectedCategory: (state, getters) => {
                return getters.categoryById(state.selectedCategoryId);
            },
            itemLevel:(state) => {
                return state.levels[state.levels.length-1];
            },
            categoryLevels: (state) => {
                let slice =  state.levels.slice(1, state.levels.length -1 );
                return slice? slice: [];
            },
            itemsCount: (state) => {
                return Object.keys(state.items).length;
            },
            categoryById: (state) => (id) => {
                return  state.categories.find (category => category.id === id);
            },
            studentById: (state) => (id) => {
                return  state.students[id];
            },
            studentSetSorted: (state, getters) => (sortStudentsMethodType) => {
                return  getters.studentSet.sort(sortStudentsMethod(sortStudentsMethodType));
            },
            studentSet: (state) => {
                return Object.values(state.students);
            },
            studentsCount: (state) => {
                return Object.keys(state.students).length;
            },
            itemSet: (state) => {
                return Object.values(state.items);
            },
            /**
             * Use this getter for item set when you should show
             * or manage items ordered, in the same order than should
             * have for the table
             * @param state
             * @param getters
             * @returns {*}
             */
            orderedItemSet: (state, getters) => {
                let itemLevel = getters.itemLevel; //see itemLevel function in getters
                if(!itemLevel) {
                    return getters.itemSet;
                }
                let orderedItemIds = itemLevel.map(element => element.object.id);
                return getters.itemSet.sort(function(itemA, itemB) {
                    return orderedItemIds.indexOf(itemA.id) - orderedItemIds.indexOf(itemB.id);
                });
            },
            categoryChildItems: (state, getters) => (idCategory) => {
                let children =  getters.itemSet.filter(item => {
                        return item.categoryid === idCategory ||
                            item.iteminstance === idCategory //
                    }
                );
                if(Array.isArray(children)) {
                    return children;
                } else {
                    return [];
                }
            },
            categoryChildCategories: (state) => (idCategory) => {
                let children =  state.categories.filter(category => category.parent === idCategory);
                return children? children: [];
            },
            categoryChildSize: (state, getters) => (idCategory) => {

                let categoryChildItems = getters.categoryChildItems(idCategory);
                let categoryChildCategories = getters.categoryChildCategories(idCategory);
                return categoryChildItems.length + categoryChildCategories.length;
            },
            categoryDepth: (state) => {
                if(state.categories.length <= 0) {
                    return 0;
                }
                var depths =  state.categories.map(category => { return category.depth; });
                return Math.max.apply(Math,depths);

            },
            getCategoriesByDepth: (state) => (depth) => {
                return state.categories.find(category=>category.depth === depth);
            }
        }
    };

    return {
        store: store,
        mutations: mutationsType,
        actions: actionsType
    }
});