<script setup>
import { defineProps, onMounted, computed } from 'vue';

const props = defineProps({
    form: Object,
    faculties: Array,
    programStudies: Array,
    isDisabled: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    isCreate: {
        type: Boolean,
        default: false,
    },
});

const getProgramStudiesByFacultyCode = (facultyCode) => {
    return props.programStudies.filter(study => study.faculty_code === facultyCode);
};

const ensureNoDuplicatePush = (item) => {
    const exists = props.form.targets.some(t => t.target_type === item.target_type && t.target_value == item.target_value);
    if (!exists) props.form.targets.push(item);
};

const removeTarget = (type, value) => {
    props.form.targets = props.form.targets.filter(target => !(target.target_type === type && target.target_value == value));
};

const handleUniversityTargetChange = (isChecked) => {
    if (isChecked) {
        props.form.targets = props.form.targets.filter(t => t.target_type === 'role');
        ensureNoDuplicatePush({ target_type: 'university', target_value: 'all' });
    } else {
        removeTarget('university', 'all');
    }
};

const hasUniversityTarget = () => {
    return props.form.targets.some(target => target.target_type === 'university' && target.target_value === 'all');
};

const handleProgramStudyTargetChange = (study, isChecked) => {
    if (isChecked) {
        ensureNoDuplicatePush({ target_type: 'program_study', target_value: study.id });
    } else {
        removeTarget('program_study', study.id);
    }

    const faculty = props.faculties.find(f => f.faculty_code === study.faculty_code);
    if (!faculty) return;

    const studiesInFaculty = getProgramStudiesByFacultyCode(faculty.faculty_code);
    const allChecked = studiesInFaculty.every(s => props.form.targets.some(t => t.target_type === 'program_study' && t.target_value == s.id));

    if (allChecked) {
        ensureNoDuplicatePush({ target_type: 'faculty', target_value: faculty.id });
    } else {
        removeTarget('faculty', faculty.id);
    }
};

const hasProgramStudyTarget = (studyId) => {
    const study = props.programStudies.find(s => s.id === studyId);
    if (!study) return false;

    const faculty = props.faculties.find(f => f.faculty_code === study.faculty_code);
    const facultyChecked = props.form.targets.some(t => t.target_type === 'faculty' && t.target_value == faculty.id);

    return facultyChecked || props.form.targets.some(target => target.target_type === 'program_study' && target.target_value == studyId);
};

const handleFacultyTargetChange = (faculty, isChecked) => {
    removeTarget('faculty', faculty.id);

    if (isChecked) {
        ensureNoDuplicatePush({ target_type: 'faculty', target_value: faculty.id });
        const relatedStudies = getProgramStudiesByFacultyCode(faculty.faculty_code);
        relatedStudies.forEach(study => {
            ensureNoDuplicatePush({ target_type: 'program_study', target_value: study.id });
        });
    } else {
        const relatedStudies = getProgramStudiesByFacultyCode(faculty.faculty_code);
        relatedStudies.forEach(study => {
            removeTarget('program_study', study.id);
        });
    }
};

const hasFacultyTarget = (facultyId) => {
    const faculty = props.faculties.find(f => f.id === facultyId);
    if (!faculty) return false;

    const studiesInFaculty = getProgramStudiesByFacultyCode(faculty.faculty_code);

    if (studiesInFaculty.length === 0) {
        return props.form.targets.some(t => t.target_type === 'faculty' && t.target_value == facultyId);
    }

    const allStudiesChecked = studiesInFaculty.every(s => props.form.targets.some(t => t.target_type === 'program_study' && t.target_value == s.id));
    const facultyExplicit = props.form.targets.some(t => t.target_type === 'faculty' && t.target_value == facultyId);

    return allStudiesChecked || facultyExplicit;
};

onMounted(() => {
    props.faculties.forEach(faculty => {
        const hasFaculty = props.form.targets.some(t => t.target_type === 'faculty' && t.target_value == faculty.id);
        if (hasFaculty) {
            getProgramStudiesByFacultyCode(faculty.faculty_code).forEach(study => {
                ensureNoDuplicatePush({ target_type: 'program_study', target_value: study.id });
            });
        }
    });
});
</script>

<template>
    <div class="col-12 mt-4">
        <div class="card bg-muted-lt">
            <div class="card-body">
                <h5 class="card-title text-primary">Filter Tambahan Mahasiswa</h5>
                <p class="card-subtitle mb-3">Pilih filter tambahan untuk penargetan mahasiswa yang lebih spesifik.</p>

                <template v-if="isEditing || isCreate">
                    <div class="mb-3">
                        <h6 class="form-label">Universitas</h6>
                        <div class="form-check">
                            <input type="checkbox" name="university_all" value="all" :checked="hasUniversityTarget()"
                                @change="handleUniversityTargetChange($event.target.checked)" class="form-check-input"
                                id="university-check" :disabled="isDisabled">
                            <label for="university-check">Universitas Potensi Utama</label>
                        </div>
                    </div>

                    <hr class="my-4" v-if="faculties.length > 0">

                    <div v-if="!hasUniversityTarget()">
                        <div v-for="(faculty, index) in faculties" :key="faculty.id" class="mb-3">
                            <h6 class="form-label">
                                <div class="form-check">
                                    <input type="checkbox" :value="faculty.id" :checked="hasFacultyTarget(faculty.id)"
                                        @change="handleFacultyTargetChange(faculty, $event.target.checked)"
                                        class="form-check-input" :id="`faculty-check-${faculty.id}`" :disabled="isDisabled">
                                    <label :for="`faculty-check-${faculty.id}`">
                                        {{ faculty.name }}
                                    </label>
                                </div>
                            </h6>
                            <div class="ms-4">
                                <div class="row">
                                    <div v-for="study in getProgramStudiesByFacultyCode(faculty.faculty_code)" :key="study.id"
                                        class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" :value="study.id" :checked="hasProgramStudyTarget(study.id)"
                                                @change="handleProgramStudyTargetChange(study, $event.target.checked)"
                                                class="form-check-input" :id="`study-check-${study.id}`"
                                                :disabled="isDisabled">
                                            <label :for="`study-check-${study.id}`">
                                                {{ study.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr v-if="index < faculties.length - 1" class="my-3">
                        </div>
                    </div>
                </template>

                <template v-else>
                    <label class="form-label">Target Mahasiswa</label>
                    <div v-if="hasUniversityTarget()">
                        <p class="form-control-plaintext">- Seluruh Mahasiswa Universitas Potensi Utama</p>
                    </div>
                    <div v-else>
                        <div v-for="(faculty, index) in faculties" :key="faculty.id" class="mb-3">
                            <h6 class="form-label" v-if="hasFacultyTarget(faculty.id) || getProgramStudiesByFacultyCode(faculty.faculty_code).some(s => form.targets.some(t => t.target_type === 'program_study' && t.target_value == s.id))">
                                {{ faculty.name }}
                            </h6>
                            <div class="ms-4">
                                <div class="row g-2">
                                    <template v-for="study in getProgramStudiesByFacultyCode(faculty.faculty_code)" :key="study.id">
                                        <div class="col-md-6" v-if="hasProgramStudyTarget(study.id)">
                                            <p class="form-control-plaintext">- {{ study.name }}</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <hr v-if="index < faculties.length - 1 && (hasFacultyTarget(faculty.id) || getProgramStudiesByFacultyCode(faculty.faculty_code).some(s => form.targets.some(t => t.target_type === 'program_study' && t.target_value == s.id)))" class="my-3">
                        </div>
                        <p v-if="!form.targets.some(t => t.target_type !== 'role')" class="form-control-plaintext">Tidak Ada Filter Tambahan</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
