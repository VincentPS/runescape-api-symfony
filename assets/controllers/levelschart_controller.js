import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
        this.element.addEventListener('chartjs:connect', this._onConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
        this.element.removeEventListener('chartjs:connect', this._onConnect);
    }

    _onPreConnect(event) {
        // The chart is not yet created
        console.log(event.detail.options); // You can access the chart options using the event details
    }

    _onConnect(event) {
        const chart = event.detail.chart;

        this.element.addEventListener('click', (event) => {
            const activeElement = chart.getElementAtEvent(event)[0];
            if (activeElement) {
                // Get the label of the clicked bar
                const label = chart.data.labels[activeElement._index];
                console.log(`Clicked on ${label}`);
            }
        });

        // The chart was just created
        console.log(event.detail.chart); // You can access the chart instance using the event details

        // For instance you can listen to additional events
        event.detail.chart.options.onHover = () => {
            /* ... */
        };
        event.detail.chart.options.onClick = () => {
            /* ... */
        };
    }
}
