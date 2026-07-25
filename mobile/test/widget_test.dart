import 'package:flutter_test/flutter_test.dart';

import 'package:modal_nekad_mobile/main.dart';

void main() {
  testWidgets('App boots to the server setup screen', (WidgetTester tester) async {
    await tester.pumpWidget(const ModalNekadApp());
    await tester.pump();

    expect(find.text('Alamat Server'), findsOneWidget);
  });
}
