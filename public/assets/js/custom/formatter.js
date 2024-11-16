"use strict";


// Bootstrap Custom Column Formatters
function fileFormatter(value, row) {
    let file_upload = "<br><h6>File Upload</h6>";
    let youtube_link = "<br><h6>Youtube Link</h6>";
    let video_upload = "<br><h6>Video Upload</h6>";
    let other_link = "<br><h6>Other Link</h6>";

    let file_upload_counter = 1;
    let youtube_link_counter = 1;
    let video_upload_counter = 1;
    let other_link_counter = 1;

    $.each(row.file, function (key, data) {
        //1 = File Upload , 2 = Youtube , 3 = Uploaded Video , 4 = Other
        if (data.type == 1) {
            // 1 = File Ulopad
            file_upload += "<a href='" + data.file_url + "' target='_blank' >" + file_upload_counter + ". " + data.file_name + "</a><br>";
            file_upload_counter++;
        } else if (data.type == 2) {
            // 2 = Youtube Link
            youtube_link += "<a href='" + data.file_url + "' target='_blank' >" + youtube_link_counter + ". " + data.file_name + "</a><br>";
            youtube_link_counter++;
        } else if (data.type == 3) {
            // 3 = Uploaded Video
            video_upload += "<a href='" + data.file_url + "' target='_blank' >" + video_upload_counter + ". " + data.file_name + "</a><br>";
            video_upload_counter++;
        } else if (data.type == 4) {
            // 4 = Other Link
            other_link += "<a href='" + data.file_url + "' target='_blank' >" + other_link_counter + ". " + data.file_name + "</a><br>";
            other_link_counter++;
        }
    })
    let html = "";
    if (file_upload_counter > 1) {
        html += file_upload;
    }

    if (youtube_link_counter > 1) {
        html += youtube_link;
    }

    if (video_upload_counter > 1) {
        html += video_upload;
    }

    if (other_link_counter > 1) {
        html += other_link;
    }

    return html;
}

function resubmissionFormatter(value, row) {
    let html = "";
    if (row.resubmission) {
        html = "<span class='alert alert-success'>YES</span>";
    } else {
        html = "<span class='alert alert-danger'>NO</span>";
    }
    return html;
}

function assignmentFileFormatter(value, row) {
    let html = "<a target='_blank' href='" + row.file + "'>" + row.name + "</a>";
    return html;
}

function assignmentSubmissionStatusFormatter(value, row) {
    let html = "";
    // 0 = Pending/In Review , 1 = Accepted , 2 = Rejected , 3 = Resubmitted
    if (row.status === 0) {
        html = "<span class='badge badge-warning'>Pending</span>";
    } else if (row.status === 1) {
        html = "<span class='badge badge-success'>Accepted</span>";
    } else if (row.status === 2) {
        html = "<span class='badge badge-danger'>Rejected</span>";
    } else if (row.status === 3) {
        html = "<span class='badge badge-warning'>Resubmitted</span>";
    }
    return html;
}

function imageFormatter(value, row) {
    if (row.image) {
        return "<a data-toggle='lightbox' href='" + row.image + "'><img src='" + row.image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>";
    } else {
        return '-'
    }
}

function notificationImageFormatter(value, row) {
    if (row.image) {
        const imageURL = 'storage/' + row.image;
        return "<a data-toggle='lightbox' href='" + imageURL + "'><img src='" + imageURL + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>";
    } else {
        return '-'
    }
}

function thumbnailFormatter(value, row) {
    if (row.thumbnail) {
        return "<a data-toggle='lightbox' href='" + row.thumbnail + "'><img src='" + row.thumbnail + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'  /></a>";
    } else {
        return '-'
    }
}

function fatherImageFormatter(value, row) {
    if(row.father_image){
        return "<a data-toggle='lightbox' href='" + row.father_image + "'><img src='" + row.father_image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>";
    }
}

function motherImageFormatter(value, row) {
    if(row.mother_image){
        return "<a data-toggle='lightbox' href='" + row.mother_image + "'><img src='" + row.mother_image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>";
    }
}

function guardianImageFormatter(value, row) {
    if(row.guardian_image){
        return "<a data-toggle='lightbox' href='" + row.guardian_image + "'><img src='" + row.guardian_image + "' class='img-fluid'  alt='image' onerror='onErrorImage(event)'/></a>";
    }
}

function examTimetableFormatter(value, row) {
    let html = []
    if (row.timetable.length != null) {
        $.each(row.timetable, function (key, timetable) {
            html.push('<p>' + timetable.subject.name + '(' + timetable.subject.type + ')  - ' + timetable.total_marks + '/' + timetable.passing_marks + ' - ' + timetable.start_time + ' - ' + timetable.end_time + ' - ' + moment(timetable.date, 'YYYY-MM-DD').format('DD-MM-YYYY') + '</p>')
        });
    }
    return html.join('')
}

function examSubjectFormatter(index, row) {
    if (row.subject_name) {
        return row.subject_name;
    } else {
        return $('#subject_id :selected').text();
    }
}

function examStudentNameFormatter(index, row) {
    return "<input type='hidden' name='exam_marks[" + row.no + "][student_id]' class='form-control' value='" + row.student_id + "' />" + row.student_name
}

function obtainedMarksFormatter(index, row) {
    if (row.obtained_marks) {
        return "<input type='hidden' name='exam_marks[" + row.no + "][exam_marks_id]' class='form-control' value='" + row.exam_marks_id + "' />" +
            "<input type='number' name='exam_marks[" + row.no + "][obtained_marks]' class='form-control' min='0' value='" + row.obtained_marks + "' />" + "<input type='hidden' name='exam_marks[" + row.no + "][total_marks]' class='form-control' value='" + parseInt(row.total_marks) + "' />"
    } else {
        return "<input type='hidden' name='exam_marks[" + row.no + "][exam_marks_id]' class='form-control' value='" + row.exam_marks_id + "' />" +
        "<input type='number' name='exam_marks[" + row.no + "][obtained_marks]' class='form-control' min='0' value='" + ' ' + "' />" +
        "<input type='hidden' name='exam_marks[" + row.no + "][total_marks]' class='form-control' value='" + parseInt(row.total_marks) + "' />"
    }
}

function teacherReviewFormatter(index, row) {
    if (row.teacher_review) {
        return "<textarea name='exam_marks[" + row.no + "][teacher_review]' class='form-control'>" + row.teacher_review + "</textarea>"
    } else {
        return "<textarea name='exam_marks[" + row.no + "][teacher_review]' class='form-control'>" + ' ' + "</textarea>"
    }
}

function examPublishFormatter(index, row) {
    if (index == 0) {
        return "<span class='badge badge-danger'>No</span>"
    } else {
        return "<span class='badge badge-success'>Yes</span>"
    }
}

function coreSubjectFormatter(value, row) {
    let core_subject_count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.core_subjects, function (key, value) {
        if (value.subject) {
            html += "<br>" + core_subject_count + ". " + value.subject.name + " - " + value.subject.type
            core_subject_count++;
        }
    })
    html += "</div>";
    return html;
}

function eventFormatter(value, row) {

    let count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.description, function (key, value) {
        console.log(value);
        if (value) {
            html += "<br>" + count + ". " + value.title +  ". " + value.start_time + " - " + value.end_time
            count++;
        }
    })
    html += "</div>";
    return html;
}

function descriptionFormatter(value, row) {
    let count = 1;
    let html = "<div style='line-height: 20px;'>";
    if(row.type == 'multiple')
    {
        $.each(row.timing, function (key, value) {
            if (value) {
                html += "<br>" + count + ". " + value.description
                count++;
            }
        })

    }else{

        html += "<br>" + row.description
    }

    html += "</div>";
    return html;
}

function classTeacherFormatter(value, row) {
    let class_teacher_count = 1;
    let html = "<div style='line-height: 20px;'>";
    $.each(row.teachers, function (key, value) {
        if (value) {
            html += "<br>" + class_teacher_count + ". " + value;
            class_teacher_count++;
        }
    })
    html += "</div>";
    return html;
}

function electiveSubjectFormatter(value, row) {
    let html = "<div style='line-height: 20px;'>";
    $.each(row.elective_subject_groups, function (key, group) {
        let elective_subject_count = 1;
        html += "<b>Group " + (key + 1) + "</b><br>";
        $.each(group.elective_subjects, function (key, elective_subject) {
            html += elective_subject_count + ". " + elective_subject.subject.name + " - " + elective_subject.subject.type + "<br>"
            elective_subject_count++;
        })
        html += "<b>Total Subjects : </b>" + group.total_subjects + "<br>"
        html += "<b>Total Selectable Subjects : </b>" + group.total_selectable_subjects + "<br><br>"
    })
    html += "</div>";
    return html;
}

function defaultYearFormatter(index, row) {
    if (index == 0) {
        return "<span class='badge badge-danger'>No</span>"
    } else {
        return "<span class='badge badge-success'>Yes</span>"
    }
}

function feesTypeChoiceable(index, row) {
    if (row.choiceable) {
        return "<span class='badge badge-success'>Yes</span>"
    } else {
        return "<span class='badge badge-danger'>No</span>"
    }
}

function feesTypeFormatter(index, row) {
    let html = [];
    if (row.fees_type.length) {
        let no = 1;
        $.each(row.fees_type, function (key, value) {
            html.push("<span>" + no + ". " + value.fees_name + " - " + value.amount + "</span><br>")
            no++;
        });
    } else {
        html.push("<p class='text-center'>-</p>")
    }
    return html.join('')

}

function feesPaidModeFormatter(index, row) {
    if (row.mode != null) {
        if (row.mode == 0) {
            return "<span class='badge badge-info'>" + lang_cash + "</span>"
        } else if (row.mode == 1) {
            return "<span class='badge badge-warning'>" + lang_cheque + "</span>"
        } else {
            return "<span class='badge badge-success'>" + lang_online + "</span>"
        }
    }
}

function feesOnlineTransactionLogParentGateway(index, row) {
    if (row.payment_gateway == 1) {
        return "<span class='badge badge-info'>RazorPay</span>";
    } else if (row.payment_gateway == 2) {
        return "<span class='badge badge-secondary'>Stripe</span>";
    } else if (row.payment_gateway == 3) {
        return "<span class='badge badge-primary'>Paystack</span>";
    }else {
        return " ";
    }
}

function feesOnlineTransactionLogPaymentStatus(index, row) {
    if (row.payment_status == 1) {
        return "<span class='badge badge-success'>" + lang_success + "</span>"
    } else if (row.payment_status == 2) {
        return "<span class='badge badge-warning'>" + lang_pending + "</span>"
    } else {
        return "<span class='badge badge-danger'>" + lang_failed + "</span>";
    }
}

function questionTypeFormatter(index, row) {
    if (row.question_type) {
        return "<span class='badge badge-secondary'>" + lang_equation_based + "</span>"
    } else {
        return "<span class='badge badge-info'>" + lang_simple_question + "</span>"
    }
}

function optionsFormatter(index, row) {
    let html = '';
    $.each(row.options, function (index, value) {
        html += "<div class='row'>";
        html += "<div class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></div><div class='col-md-6'>" + value.option + "</div><br>"
        html += "</div>";
    });
    return html;
}

function answersFormatter(index, row) {
    let html = '';
    $.each(row.answers, function (index, value) {
        html += "<div class='row'>";
        html += "<span class= 'col-md-1 text-center'><i class='fa fa-arrow-right small' aria-hidden='true'></i></span><div class='col-md-6'>" + value.answer + "</div><br>"
        html += "</div>";
    });
    return html;
}

function feesPaidStatusFormatter(value, row, index) {
    if (row.fees_status == 1) {
        return "<span class='badge badge-success'>" + lang_success + "</span>"
    } else if (row.fees_status == 0) {
        return "<span class='badge badge-info'>" + lang_partial_paid + "</span>"
    } else {
        return "<span class='badge badge-warning'>" + lang_pending + "</span>";
    }
}

function textFormatter(value, row) {
    if (row.text) {
        console.log(row.text);
        if (row.text.length > 20) {
            return "<p>" + row.text.substring(0, 100) + "..." + "</p>";
        } else {
            return "<p>" + row.text + "</p>";
        }
    } else {
        return "-";
    }
}

function messageFormatter(value, row) {
    if (row.message) {

        if (row.message.length > 20) {
            return "<p>" + row.message.substring(0, 50) + "..." + "</p>";
        } else {
            return "<p>" + row.message + "</p>";
        }
    } else {
        return "-";
    }
}

function shiftStatusFormatter(value, row, index) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + lang_active + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_inactive + "</span>";
    }
}

function StatusFormatter(value, row, index) {
    if (row.status == 1) {
        return "<span class='badge badge-success'>" + lang_enable + "</span>";
    } else {
        return "<span class='badge badge-danger'>" + lang_disable + "</span>";
    }
}

function sliderTypeFormatter(index, row) {
    if (row.type == 1) {
        return "<span class='badge badge-success'>App</span>"
    } else if(row.type == 2) {
        return "<span class='badge badge-secondary'>Web</span>"
    }else if(row.type == 3){
        return "<span class='badge badge-warning'>Web/App</span>"
    }else{
        return ""
    }
}

$('#filter_exam_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_medium_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_shift_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_subject_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_class_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})


$('#filter_class_section_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_teacher_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter_subject_id').on('change', function () {
    $('#table_list').bootstrapTable('refresh');
})

$('#filter-question-class-section-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})

$('#filter-question-subject-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})

$('#filter-view-question-class-id').on('change', function () {
    $('#table_list_questions').bootstrapTable('refresh');
})

function reasonFormatter(value, row) {
    if (row.reason) {
        if (row.reason.length > 20) {
            return "<p>" + row.reason.substring(0, 30) + "..." + "</p>";
        } else {
            return "<p>" + row.reason + "</p>";
        }
    } else {
        return "-";
    }
}
