/**
 * Mencari kriteria label & warna berdasarkan persentase
 */
export function getCriterion(rawPercentage, criteria) {
    if (!criteria || criteria.length === 0) {
        return { label: 'Belum ada kriteria', color: '#ccc' };
    }

    const score = parseFloat(rawPercentage);
    const roundedScore = Math.round(score); // Solusi celah desimal

    const found = criteria.find(c => {
        const min = parseFloat(c.min_value);
        const max = parseFloat(c.max_value);
        return roundedScore >= min && roundedScore <= max;
    });

    return found || { label: 'Tidak Terdefinisi', color: '#6c757d' };
}

/**
 * Menghitung skor rata-rata untuk satu pertanyaan
 */
export function calculateQuestionScore(answers, options) {
    if (!answers || answers.length === 0 || !options || options.length === 0) {
        return { percentage: 0, average: 0 };
    }

    // 1. Cari nilai maksimum opsi (misal skala 5)
    const maxOptionValue = Math.max(...options.map(o => o.option_value));

    // 2. Hitung total nilai jawaban
    const sumValues = answers.reduce((sum, a) => sum + parseInt(a.answer_value || 0), 0);

    // 3. Hitung rata-rata
    const avgValue = sumValues / answers.length;

    // 4. Hitung persentase
    const percentage = (avgValue / maxOptionValue) * 100;

    return {
        percentage: percentage.toFixed(1),
        average: avgValue.toFixed(2),
        maxValue: maxOptionValue
    };
}
