import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        super.connect();

        //Get the button
        let mybutton = document.getElementById("btn-back-to-top");

        // When the user scrolls down 20px from the top of the document, show the button
        window.onscroll = function () {
            if (
                document.body.scrollTop > 20 ||
                document.documentElement.scrollTop > 20
            ) {
                mybutton.style.display = "block";
            } else {
                mybutton.style.display = "none";
            }
        };

        // When the user clicks on the button, scroll to the top of the document
        mybutton.addEventListener("click", function () {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        });
    }
}
