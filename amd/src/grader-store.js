define([
    'local_customgrader/grader-utils',
    'local_customgrader/grader-enums',
    'local_customgrader/grader-service',
    'local_customgrader/vendor-vue',
    'local_customgrader/vendor-lodash'
], function (g_utils, g_enums, g_service, Vue, _) {

    const columnFinalGrade = {text: "Nota final"};
    const columnStudentCode= {text: "Código estudiante"};
    const columnStudentNames= {text: "", hide: true};


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
    /**
     * Return an array of students sorted
     * @param sortStudentMethodType
     * @param students {Array<Student>}
     * @returns {function(*, *): boolean}
     */
    var sortStudents = function (students, sortStudentMethodType) {
        switch (sortStudentMethodType.name) {
            case g_enums.sortStudentMethods.FIRST_NAME:
                return _.orderBy(students, ['firstname'], sortStudentMethodType.order);
            case g_enums.sortStudentMethods.LAST_NAME:
                return _.orderBy(students, ['lastname'], sortStudentMethodType.order);
        }
    };
    var mutationsType = {
        SET_STATE: 'setAllState',
        ADD_GRADE: 'addGrade',
        SET_GRADE: 'setGrade',
        SET_GRADES: 'setGrades',
        SET_CATEGORY: 'setCategory',
        SET_STUDENT_SORT_METHOD: 'setStudentSortMethod',
        SET_LEVELS: 'setLevels',
        SET_ITEM: 'setItem',
        ADD_ITEM: 'addItem',
        ADD_GRADE_TO_STUDENT: 'addGradeToStudent',
        DELETE_ITEM: 'deleteItem',
        DELETE_GRADE: 'deleteItemGrades',
        SET_SELECTED_CATEGORY_ID: 'setSelectedCategoryId'
    };
    var actionsType = {
        FETCH_STATE: 'fetchAllState',
        FILL_GRADES: 'fillGrades',
        FILL_GRADES_FOR_NEW_ITEM: 'fillGradesForNewItem',
        UPDATE_GRADE: 'updateGrade',
        DELETE_ITEM: 'deleteItem',
        UPDATE_CATEGORY: 'setCategory',
        ADD_ITEM: 'addItem',
        UPDATE_ITEM: 'setItem',
        DELETE_ITEM_GRADES: 'deleteItemGrades'
    };
    var store = {

        state : {
            aggregations: aggregations,
            additionalColumnsAtFirst: [
                columnStudentCode,
                columnStudentNames
            ],
            additionalColumnsAtEnd: [
                columnFinalGrade
            ],
            sortStudentsMethodType: {
                name: g_enums.sortStudentMethods.LAST_NAME,
                order: g_enums.sortDirection.ASC
            },
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
            [mutationsType.SET_STUDENT_SORT_METHOD](state, sortMethodType) {
                state.sortStudentsMethodType = sortMethodType;
            },
            [mutationsType.DELETE_GRADE] (state, gradeId) {
                Object.keys(state.students).forEach( studentId => {
                        const student = state.students[studentId];
                        Vue.set(
                            state.students[studentId],
                            'gradeIds',
                            student.gradeIds.filter(_gradeId => _gradeId !== gradeId)
                        )
                    }
                );
                Vue.delete(state.grades, gradeId);
            },
            [mutationsType.ADD_ITEM](state, item) {
                Vue.set(state.items, item.id, item);
            },
            [mutationsType.ADD_GRADE] (state, grade) {
                grade.id = g_utils.ID();
                let student = state.students[grade.userid];
                let studentGradeIds = student.gradeIds? student.gradeIds: [];
                Vue.set(state.grades, grade.id, grade);
                Vue.set(state.students[student.id], 'gradeIds', [...studentGradeIds, grade.id]);
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
            [mutationsType.SET_GRADES] (state, newGrades) {
                newGrades.forEach(newGrade => {
                    if(newGrade !== state.grades[newGrade.id]) {
                        Vue.set(state.grades, newGrade.id, newGrade);
                    }
              })  ;
            },
            [mutationsType.SET_GRADE] (state, payload) {
                let oldGrade = payload.old;
                let newGrade = payload.new;
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
                    gradesDict[grade.id] = {...grade, finalgrade: g_utils.removeInsignificantTrailZeros(grade.finalgrade)};
                });
                state.grades = gradesDict;
                state.course = newState.course;
            }
        },
        actions: {
            [actionsType.DELETE_ITEM] ({commit, dispatch, state}, itemId) {
                g_service.delete_item(itemId)
                    .then( response => {
                        commit(mutationsType.SET_LEVELS, response.levels);
                        commit(mutationsType.DELETE_ITEM, itemId);
                        dispatch(actionsType.DELETE_ITEM_GRADES, itemId);
                    });
            },
            [actionsType.ADD_ITEM] ({commit, dispatch}, item) {
              g_service.add_item(item)
                  .then(response => {
                      commit(mutationsType.ADD_ITEM, response.item);
                      commit(mutationsType.SET_LEVELS, response.levels);
                      dispatch(actionsType.FILL_GRADES_FOR_NEW_ITEM, response.item);
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
            [actionsType.FILL_GRADES_FOR_NEW_ITEM] ({commit, state, getters}, item) {
                let studentIds = Object.keys(state.students);
                studentIds.forEach(studentId => {
                    let grade = {
                        userid: studentId,
                        itemid: item.id,
                        finalgrade: null,
                        rawgrademin: item.grademin,
                        rawgrademax: item.grademax
                    };
                    commit(mutationsType.ADD_GRADE, grade);
                });
            },
            /**
             * When the grades are retrieved by the backend, only the grades graded are returned,
             * items without grades are no returned, in the interface we need all grades for
             * each student in each item, if the item is not graded, a fake grade is created and added
             * in `grades` and `studentGradeIds`
             * @param commit
             * @param state
             * @param getters
             */
            [actionsType.FILL_GRADES] ({ commit, state, getters }) {
                let studentIds = Object.keys(state.students);
                let grades = Object.values(state.grades);
                studentIds.forEach(studentId => {
                    for(var itemId of getters.itemOrderIds /* The grades are printed in this order*/) {
                        let item = state.items[itemId];
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
                        console.log(response, 'response at update grade');
                        commit(mutationsType.SET_GRADE, {old: grade, new: response.grade});
                        commit(mutationsType.SET_GRADES, response.other_grades);
                    });
            },
            [actionsType.UPDATE_CATEGORY]({dispatch, commit}, category) {
                g_service.update_category(category)
                    .then( response => {
                        console.log(response, 'response at update category')
                        let category = response.category;
                        let levels = response.levels;
                        commit(mutationsType.SET_CATEGORY, category);
                        commit(mutationsType.SET_LEVELS, levels);
                    })
            },
            [actionsType.UPDATE_ITEM]({dispatch, commit}, item) {
                console.log(item, 'item at upgrade item')
                g_service.update_item(item)
                    .then( response => {
                        console.log(response, 'response at update item')
                        let item = response.item;
                        let levels = response.levels;
                        commit(mutationsType.SET_ITEM, item);
                        commit(mutationsType.SET_LEVELS, levels);
                    })
            },
            [actionsType.FETCH_STATE] ({dispatch, commit}) {
                g_service.get_grader_data(g_utils.getCourseId())
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
            studentSetSorted: (state, getters) => {
                return  sortStudents(getters.studentSet, state.sortStudentsMethodType);
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
             * Use this getter newGradenewGrader item set when you should show
             * or manage items ordered, in the same order than should
             * have for the table
             * @param state
             * @param getters
             * @returns {*}
             */
            itemOrderIds: (state, getters) => {
                let itemLevel = getters.itemLevel; //see itemLevel function in getters
                if(!itemLevel) {
                    return Object.keys(state.items);
                }
                return itemLevel.map(element => element.object.id);
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