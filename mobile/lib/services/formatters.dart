import 'package:intl/intl.dart';

final _rupiahFormat = NumberFormat.decimalPattern('id_ID');

String formatRupiah(dynamic value) {
  final number = value is String ? double.tryParse(value) ?? 0 : (value ?? 0).toDouble();
  return 'Rp ${_rupiahFormat.format(number)}';
}

String formatDate(String? isoDate) {
  if (isoDate == null || isoDate.isEmpty) return '-';
  try {
    final date = DateTime.parse(isoDate).toLocal();
    return DateFormat('d MMM yyyy', 'id_ID').format(date);
  } catch (_) {
    return isoDate;
  }
}
