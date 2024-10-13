1. PDF Upload: The application starts by receiving a PDF file uploaded by the user through a POST request.
2. Move File to Server: The PDF file is saved to the uploads/ directory on the server.
3. PDF Text Extraction: The parsePDF() function is called, which uses the Smalot\PdfParser\Parser to extract the text content from the PDF file.
4. The application checks if the document contains multiple bookings by counting occurrences of the keyword REFERENCE. If there is more than one booking, the document is split into separate bookings.
5. If there are multiple bookings, the text is split accordingly and each booking is processed individually by calling the processBooking() method.
6. For each booking, the processBooking() method determines if the booking is:
    a. A modified booking.
    b. A round trip booking.
    c. A one-way booking.
    d. Also other types are fetched and assigned as bookingType.
7. The method uses the position of keywords ("Modification", "Departure" and "Arrival") for processing the booking accordingly.
8. The method extractValues() is used to extract specific booking details based on patterns of regular expressions stored in the Patterns class.
9. After the booking details are extracted, the method assignTransfersPrices() assigns relevant pricing details to the transfer object based on the city, vehicle type and airport.
10. Prices are fetched from the database using the getPriceDetails() method, which queries based on city, vehicle and airport.
11. The method assignTransfersPrices() assigns the fetched price details (client price, contractor price and GTU) to the transfer object.
12. Once all bookings are processed, they are outputted in JSON format.